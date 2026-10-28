<?php

namespace App\Domain;

use App\Jobs\RunDocumentCheck;
use App\Models\Document;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public const TYPES = ['commercial_invoice', 'packing_list', 'certificate_of_origin', 'bl', 'awb', 'msds', 'arrival_notice', 'delivery_order', 'receipt', 'whatsapp_chat', 'voice_note', 'rfq_attachment', 'other'];

    public const CHECKABLE = ['commercial_invoice', 'packing_list', 'certificate_of_origin', 'bl', 'awb'];

    public const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,webp,xlsx,xls,csv,doc,docx,txt,zip,opus,ogg,oga,m4a,mp3,mp4,aac,wav';

    public const MAX_KB = 20480;

    public function store(UploadedFile $upload, string $type, User $user, ?ShipmentFile $f = null, ?int $clientId = null): Document
    {
        $doc = Document::create([
            'file_id' => $f?->id, 'client_id' => $clientId ?? $f?->client_id, 'type' => $type,
            'original_name' => Str::limit($upload->getClientOriginalName(), 200, ''), 'path' => '',
            'mime' => $upload->getMimeType() ?? 'application/octet-stream', 'size' => $upload->getSize(), 'uploaded_by' => $user->id,
        ]);
        $ext = strtolower($upload->getClientOriginalExtension() ?: $upload->guessExtension() ?: 'bin');
        $path = $upload->storeAs('documents/'.($f?->id ?? 'client-'.$doc->client_id), $doc->id.'-'.Str::random(8).'.'.$ext, 'private');
        $doc->update(['path' => $path]);

        if ($f && in_array($type, self::CHECKABLE, true) && (Settings::get('ai')['doc_check'] ?? false)) {
            RunDocumentCheck::dispatch($f->id, $user->id)->delay(now()->addSeconds(20));
        }
        return $doc;
    }

    public function download(Document $doc)
    {
        abort_unless(Storage::disk('private')->exists($doc->path), 404);
        return Storage::disk('private')->download($doc->path, $doc->original_name);
    }
}
