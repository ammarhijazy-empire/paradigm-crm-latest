<?php

namespace App\Ai\Features;

use App\Ai\AiException;
use App\Ai\TextExtractor;
use App\Domain\InquiryValidator;
use App\Models\AiRun;
use App\Models\Document;
use App\Models\Port;
use Illuminate\Support\Facades\Storage;

/** 13.1 — quote request filled from WhatsApp chat text, exported chats and voice notes. */
class WhatsappRfq extends BaseFeature
{
    protected function feature(): string
    {
        return 'whatsapp_rfq';
    }

    public function handle(AiRun $run): AiRun
    {
        return $this->run($run, function () use ($run) {
            $x = app(TextExtractor::class);
            $chat = (string) ($run->input_ref['text'] ?? '');
            $transcripts = [];
            $audioSeconds = 0.0;
            foreach (Document::whereIn('id', $run->input_ref['document_ids'] ?? [])->get() as $doc) {
                $abs = Storage::disk('private')->path($doc->path);
                $ext = strtolower(pathinfo($doc->original_name, PATHINFO_EXTENSION));
                if (in_array($ext, ['opus', 'ogg', 'oga', 'm4a', 'mp3', 'aac', 'wav', 'mp4'], true)) {
                    $t = $this->ai->transcribe($abs, $doc->original_name);
                    $transcripts[] = ['name' => $doc->original_name, 'text' => $t['text']];
                    $audioSeconds += $t['seconds'];
                } elseif ($ext === 'zip') {
                    $chat .= "\n".$x->whatsappZip($abs);
                    foreach ($x->zipAudio($abs) as $a) {
                        $t = $this->ai->transcribe($a['path'], $a['name']);
                        $transcripts[] = ['name' => $a['name'], 'text' => $t['text']];
                        $audioSeconds += $t['seconds'];
                        @unlink($a['path']);
                    }
                } else {
                    $chat .= "\n".($x->extract($abs, $doc->mime, $doc->original_name)['text'] ?? '');
                }
            }
            if (trim($chat) === '' && ! $transcripts) {
                throw new AiException(__('ai.nothing_to_read'));
            }
            $content = "CHAT:\n".mb_substr($chat, 0, TextExtractor::MAX_CHARS);
            foreach ($transcripts as $t) {
                $content .= "\n\nVOICE NOTE ({$t['name']}):\n".$t['text'];
            }
            $res = $this->ai->json($this->prompt(), [['type' => 'text', 'text' => $content]]);
            $out = self::normalize($res['data']);
            $out['transcripts'] = $transcripts;
            return [$out, $res['usage'], $res['model'], $audioSeconds];
        });
    }

    /** Keeps only values our form accepts; everything else is dropped. */
    public static function normalize(array $d): array
    {
        $service = self::oneOf($d['service'] ?? null, ['fcl', 'lcl', 'air', 'land', 'bulk', 'roro', 'se', 'courier']);
        $rows = [];
        foreach (array_slice((array) ($d['rows'] ?? []), 0, 20) as $r) {
            if (! is_array($r)) {
                continue;
            }
            $rows[] = array_filter([
                'type' => self::oneOf($r['type'] ?? null, array_merge(InquiryValidator::CONTAINERS, ['Flat Rack', 'Open Top'])),
                'truck' => self::oneOf($r['truck'] ?? null, ['FTL', 'LTL']),
                'count' => self::int($r['count'] ?? null), 'pieces' => self::int($r['pieces'] ?? null),
                'l' => self::num($r['l'] ?? null), 'w' => self::num($r['w'] ?? null), 'h' => self::num($r['h'] ?? null),
                'kg' => self::num($r['kg'] ?? null), 'tonnes' => self::num($r['tonnes'] ?? null),
            ], fn ($v) => $v !== null);
        }
        $out = [
            'direction' => self::oneOf($d['direction'] ?? null, ['import', 'export']),
            'service' => $service,
            'addons' => array_values(array_intersect((array) ($d['addons'] ?? []), ['clearance', 'trucking', 'insurance', 'other'])),
            'rows' => $rows,
            'origin' => self::matchPort($d['origin_text'] ?? null),
            'destination' => self::matchPort($d['destination_text'] ?? null),
            'origin_text' => self::str($d['origin_text'] ?? null),
            'destination_text' => self::str($d['destination_text'] ?? null),
            'incoterm' => self::oneOf($d['incoterm'] ?? null, InquiryValidator::INCOTERMS_SEA),
            'pickup_address' => self::str($d['pickup_address'] ?? null),
            'delivery_address' => self::str($d['delivery_address'] ?? null),
            'inq' => array_filter([
                'commodity' => self::str($d['commodity'] ?? null),
                'cargo' => self::oneOf($d['cargo'] ?? null, ['general', 'hazardous', 'reefer', 'fragile', 'liquid', 'oog']) ?? 'general',
                'ready' => self::date($d['ready_date'] ?? null),
                'price_by' => self::date($d['price_by'] ?? null),
                'free_days' => self::int($d['free_days'] ?? null),
                'freight_terms' => self::oneOf($d['freight_terms'] ?? null, ['prepaid', 'collect']),
                'pay' => self::oneOf($d['pay'] ?? null, ['cash_in_advance', 'against_documents', 'on_arrival', 'credit']),
                'credit_days' => self::int($d['credit_days'] ?? null),
                'notes' => self::str($d['notes'] ?? null, 1000),
            ], fn ($v) => $v !== null),
            'still_to_ask' => array_values(array_filter(array_map(fn ($q) => self::str($q, 200), array_slice((array) ($d['still_to_ask'] ?? []), 0, 10)))),
        ];
        $out['filled'] = array_keys(array_filter([
            'direction' => $out['direction'], 'service' => $out['service'], 'addons' => $out['addons'], 'rows' => $out['rows'],
            'origin' => $out['origin'], 'destination' => $out['destination'], 'incoterm' => $out['incoterm'],
            'pickup_address' => $out['pickup_address'], 'delivery_address' => $out['delivery_address'],
        ] + collect($out['inq'])->mapWithKeys(fn ($v, $k) => ["inq.$k" => $v])->all()));
        return $out;
    }

    public static function matchPort(?string $text): ?string
    {
        $t = self::str($text);
        if (! $t) {
            return null;
        }
        $code = mb_strtoupper(preg_replace('/\s+/', '', $t));
        if ($p = Port::where('code', $code)->value('code')) {
            return $p;
        }
        $word = trim(preg_split('/[,،\-(]/u', $t)[0]);
        return Port::where('active', true)->where(fn ($q) => $q->where('name_en', 'like', $word.'%')->orWhere('name_ar', 'like', $word.'%'))->value('code');
    }
}
