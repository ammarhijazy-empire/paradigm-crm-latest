<?php

namespace App\Jobs;

use App\Ai\AiGuard;
use App\Models\AiRun;
use App\Models\InboundEmail;
use App\Models\ShipmentFile;
use App\Models\Vendor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\ClientManager;

/** Reads the shared rates mailbox over IMAP every 5 minutes (scheduled). */
class FetchRateEmails implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 280;

    public function handle(AiGuard $guard): void
    {
        $cfg = config('crm.rates_mailbox');
        if (! $cfg['enabled'] || ! $cfg['username']) {
            return;
        }
        $client = (new ClientManager)->make([
            'host' => $cfg['host'], 'port' => $cfg['port'], 'encryption' => $cfg['encryption'], 'validate_cert' => true,
            'username' => $cfg['username'], 'password' => $cfg['password'], 'protocol' => 'imap',
        ]);
        $client->connect();
        $folder = $client->getFolder($cfg['folder']);
        foreach ($folder->query()->unseen()->since(now()->subDays(7))->limit(30)->get() as $msg) {
            $mid = (string) ($msg->getMessageId() ?: Str::uuid());
            if (InboundEmail::where('message_id', $mid)->exists()) {
                $msg->setFlag('Seen');
                continue;
            }
            $from = mb_strtolower((string) ($msg->getFrom()[0]->mail ?? ''));
            $subject = (string) $msg->getSubject();
            $mail = InboundEmail::create([
                'message_id' => $mid, 'from_email' => $from, 'from_name' => (string) ($msg->getFrom()[0]->personal ?? ''),
                'subject' => mb_substr($subject, 0, 250), 'body_text' => mb_substr((string) ($msg->getTextBody() ?: strip_tags((string) $msg->getHTMLBody())), 0, 100000),
                'received_at' => $msg->getDate()?->toDate() ?? now(), 'status' => 'new',
            ]);
            $att = [];
            foreach ($msg->getAttachments() as $a) {
                if (count($att) >= 6 || $a->getSize() > 15 * 1024 * 1024) {
                    continue;
                }
                $path = 'inbound/'.$mail->id.'/'.Str::random(6).'-'.Str::slug(pathinfo($a->getName(), PATHINFO_FILENAME)).'.'.strtolower(pathinfo($a->getName(), PATHINFO_EXTENSION));
                Storage::disk('private')->put($path, $a->getContent());
                $att[] = ['name' => $a->getName(), 'path' => $path, 'mime' => $a->getMimeType(), 'size' => $a->getSize()];
            }
            $vendor = Vendor::where('email', $from)->first() ?? Vendor::where('email', 'like', '%@'.Str::after($from, '@'))->first();
            $file = null;
            $method = null;
            if (preg_match('/\b([A-Z]{2,4}-\d{4}-\d+)-OPS\b/', $subject.' '.$mail->body_text, $m)) {
                $file = ShipmentFile::withoutGlobalScopes()->where('ref', $m[1])->first();
                $method = $file ? 'reference' : null;
            }
            if (! $file && $vendor) {
                $file = ShipmentFile::withoutGlobalScopes()->where('status', 'open')->whereIn('pricing_status', ['pending', 'review'])
                    ->whereHas('history', fn ($q) => $q->where('action', 'rfq_sent')->where('note', 'like', '%'.$vendor->name.'%'))->latest()->first();
                $method = $file ? 'sender' : null;
            }
            $mail->update(['attachments' => $att, 'vendor_id' => $vendor?->id, 'file_id' => $file?->id, 'match_method' => $method, 'status' => ($file && $vendor) ? 'new' : 'unmatched']);
            if ($guard->enabled('vendor_email')) {
                $run = AiRun::create(['feature' => 'vendor_email', 'file_id' => $file?->id, 'status' => 'queued', 'input_ref' => ['inbound_email_id' => $mail->id], 'subject_type' => $mail->getMorphClass(), 'subject_id' => $mail->id]);
                RunAiFeature::dispatch($run->id);
            }
            $msg->setFlag('Seen');
        }
        $client->disconnect();
    }
}
