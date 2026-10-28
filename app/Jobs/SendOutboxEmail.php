<?php

namespace App\Jobs;

use App\Mail\OutboxMessage;
use App\Models\EmailOutbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOutboxEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public int $outboxId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $row = EmailOutbox::find($this->outboxId);
        if (! $row || $row->status === 'sent') {
            return;
        }
        $row->increment('attempts');
        try {
            Mail::to($row->to)->cc($row->cc ?? [])->send(new OutboxMessage($row));
            $row->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
        } catch (Throwable $e) {
            $row->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
            throw $e;
        }
    }
}
