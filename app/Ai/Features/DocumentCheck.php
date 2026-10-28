<?php

namespace App\Ai\Features;

use App\Ai\AiException;
use App\Ai\TextExtractor;
use App\Domain\DocumentService;
use App\Events\CrmEvent;
use App\Models\AiRun;
use App\Models\DocCheck;
use App\Models\ShipmentFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** 13.3 — cross-checks the shipping documents before customs. */
class DocumentCheck extends BaseFeature
{
    protected function feature(): string
    {
        return 'doc_check';
    }

    public function handle(AiRun $run): AiRun
    {
        $file = ShipmentFile::withoutGlobalScopes()->with('client', 'documents')->findOrFail($run->file_id);
        $docs = $file->documents->whereIn('type', DocumentService::CHECKABLE)->take(8);
        if ($docs->isEmpty()) {
            $run->update(['status' => 'failed', 'error' => __('ai.no_documents')]);
            throw new AiException(__('ai.no_documents'));
        }
        $this->run($run, function () use ($file, $docs) {
            $x = app(TextExtractor::class);
            // File facts. Client contact details are not needed and never sent.
            $facts = [
                'file' => $file->number, 'direction' => $file->direction, 'service' => $file->service, 'incoterm_quoted' => $file->incoterm,
                'consignee_company' => $file->direction === 'import' ? $file->client->company : null,
                'shipper_company' => $file->direction === 'export' ? $file->client->company : null,
                'origin' => $file->origin, 'destination' => $file->destination, 'acid_no' => $file->acid_no,
                'bl_no' => $file->bl_no, 'carrier' => $file->carrier, 'commodity' => $file->inq['commodity'] ?? null, 'volume' => $file->inq['rows'] ?? [],
            ];
            $parts = [['type' => 'text', 'text' => "FILE DATA:\n".json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]];
            foreach ($docs as $d) {
                $r = $x->extract(Storage::disk('private')->path($d->path), $d->mime, $d->original_name);
                if ($r['text']) {
                    $parts[] = ['type' => 'text', 'text' => "\n\nDOCUMENT type={$d->type} id={$d->id} name={$d->original_name}:\n".mb_substr($r['text'], 0, 15000)];
                } elseif ($r['image']) {
                    $parts[] = ['type' => 'text', 'text' => "\n\nDOCUMENT type={$d->type} id={$d->id} name={$d->original_name} (scan below)"];
                    $parts[] = ['type' => 'image_url', 'image_url' => ['url' => $r['image']]];
                }
            }
            $res = $this->ai->json($this->prompt(), $parts, config('crm.ai.vision_model'));
            return [self::normalize($res['data']), $res['usage'], $res['model']];
        });

        $out = $run->fresh()->output;
        $byType = $docs->keyBy('type');
        DB::transaction(function () use ($file, $run, $out, $byType) {
            $dismissed = $file->docChecks()->where('status', 'dismissed')->get()->map(fn ($c) => ($c->document?->type ?? 'file').'|'.$c->field.'|'.$c->severity);
            $file->docChecks()->where('status', 'open')->update(['status' => 'fixed', 'resolved_at' => now()]);
            foreach ($out['issues'] as $i) {
                if ($dismissed->contains($i['document'].'|'.$i['field'].'|'.$i['severity'])) {
                    continue;
                }
                DocCheck::create([
                    'file_id' => $file->id, 'ai_run_id' => $run->id, 'document_id' => $byType[$i['document']]->id ?? null,
                    'severity' => $i['severity'], 'field' => $i['field'], 'message' => json_encode(['en' => $i['message_en'], 'ar' => $i['message_ar']], JSON_UNESCAPED_UNICODE),
                    'status' => $i['severity'] === 'info' ? 'fixed' : 'open',
                ]);
            }
        });
        $blockers = $file->openBlockers();
        event(new CrmEvent('doc_check_done', ['ref' => $file->number, 'n' => $blockers], route('files.show', $file).'?tab=docs', ['ops', 'customs'], [$file->sales_id]));
        return $run;
    }

    public static function normalize(array $d): array
    {
        $issues = [];
        foreach (array_slice((array) ($d['issues'] ?? []), 0, 30) as $i) {
            $sev = self::oneOf($i['severity'] ?? null, ['blocker', 'warning', 'info']);
            $en = self::str($i['message_en'] ?? null, 500);
            if (! $sev || ! $en) {
                continue;
            }
            $issues[] = [
                'severity' => $sev, 'document' => self::oneOf($i['document'] ?? null, ['commercial_invoice', 'packing_list', 'certificate_of_origin', 'bl', 'awb', 'file']) ?? 'file',
                'field' => self::str($i['field'] ?? null, 60) ?? '-', 'message_en' => $en, 'message_ar' => self::str($i['message_ar'] ?? null, 500) ?? $en,
            ];
        }
        return ['issues' => $issues, 'summary_en' => self::str($d['summary_en'] ?? null, 500), 'summary_ar' => self::str($d['summary_ar'] ?? null, 500)];
    }
}
