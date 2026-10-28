<?php

namespace App\Ai\Features;

use App\Ai\AiException;
use App\Ai\TextExtractor;
use App\Domain\PricingService;
use App\Events\CrmEvent;
use App\Models\AiRun;
use App\Models\InboundEmail;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** 13.2 — reads a vendor rate email and its attachments into draft vendor quotes. */
class VendorEmail extends BaseFeature
{
    protected function feature(): string
    {
        return 'vendor_email';
    }

    public function handle(AiRun $run): AiRun
    {
        $mail = InboundEmail::findOrFail($run->input_ref['inbound_email_id']);
        $mail->update(['status' => 'reading']);
        try {
            $this->run($run, function () use ($mail) {
                $x = app(TextExtractor::class);
                $parts = [['type' => 'text', 'text' => "FROM: {$mail->from_name} <{$mail->from_email}>\nSUBJECT: {$mail->subject}\n\nBODY:\n".mb_substr((string) $mail->body_text, 0, 30000)]];
                foreach (array_slice($mail->attachments ?? [], 0, 6) as $a) {
                    $r = $x->extract(Storage::disk('private')->path($a['path']), $a['mime'] ?? '', $a['name']);
                    if ($r['text']) {
                        $parts[] = ['type' => 'text', 'text' => "\n\nATTACHMENT {$a['name']}:\n".mb_substr($r['text'], 0, 20000)];
                    } elseif ($r['image']) {
                        $parts[] = ['type' => 'image_url', 'image_url' => ['url' => $r['image']]];
                    }
                }
                $res = $this->ai->json($this->prompt(), $parts, count($parts) > 1 ? config('crm.ai.vision_model') : null);
                return [self::normalize($res['data']), $res['usage'], $res['model']];
            });
        } catch (AiException $e) {
            $mail->update(['status' => 'failed']);
            throw $e;
        }

        $out = $run->fresh()->output;
        $file = $mail->file;
        if (! $file && $out['reference'] && preg_match('/^([A-Z]{2,4}-\d{4}-\d+)/', $out['reference'], $m)) {
            $file = \App\Models\ShipmentFile::withoutGlobalScopes()->where('ref', $m[1])->first();
            $file && $mail->update(['file_id' => $file->id, 'match_method' => 'reference']);
        }
        if (! $file || ! $mail->vendor_id) {
            $mail->update(['status' => 'unmatched']);
            event(new CrmEvent('vendor_email_unmatched', ['from' => $mail->from_email], route('pricing.index'), ['pricing']));
            return $run;
        }
        $pricing = app(PricingService::class);
        $system = $run->user; // null for mailbox runs: the AI drafted it, a person confirms it
        foreach ($out['offers'] as $o) {
            $pricing->recordQuote($file, $o + ['vendor_id' => $mail->vendor_id], $system, 'ai', $mail->id, 'draft');
        }
        $mail->update(['status' => 'read']);
        $run->update(['file_id' => $file->id]);
        event(new CrmEvent('vendor_quotes_drafted', ['ref' => $file->ref, 'n' => count($out['offers'])], route('files.show', $file).'?tab=pricing', ['pricing']));
        return $run;
    }

    public static function normalize(array $d): array
    {
        $offers = [];
        foreach (array_slice((array) ($d['offers'] ?? []), 0, 5) as $o) {
            if (! is_array($o) || self::num($o['freight'] ?? null) === null) {
                continue;
            }
            $cur = is_string($o['currency'] ?? null) && preg_match('/^[A-Z]{3}$/', $o['currency']) ? $o['currency'] : 'USD';
            $offers[] = [
                'carrier' => self::str($o['carrier'] ?? null), 'routing' => self::str($o['routing'] ?? null), 'currency' => $cur,
                'freight' => self::num($o['freight']),
                'surcharges' => array_values(array_filter(array_map(fn ($s) => is_array($s) && self::str($s['name'] ?? null) && self::num($s['amount'] ?? null) !== null ? ['name' => self::str($s['name'], 60), 'amount' => self::num($s['amount'])] : null, (array) ($o['surcharges'] ?? [])))),
                'origin_charges' => self::num($o['origin_charges'] ?? 0) ?? 0, 'destination_charges' => self::num($o['destination_charges'] ?? 0) ?? 0,
                'transit_days' => self::int($o['transit_days'] ?? null), 'free_days' => self::int($o['free_days'] ?? null),
                'valid_until' => self::date($o['valid_until'] ?? null), 'etd' => self::date($o['etd'] ?? null),
                'conditions' => self::str($o['conditions'] ?? null, 1000),
                'confidence' => ['overall' => self::oneOf($d['confidence'] ?? null, ['high', 'medium', 'low']) ?? 'low'],
            ];
        }
        return [
            'reference' => self::str($d['reference'] ?? null, 40),
            'offers' => $offers,
            'missing' => array_values(array_filter(array_map(fn ($m) => self::str($m, 200), (array) ($d['missing'] ?? [])))),
        ];
    }
}
