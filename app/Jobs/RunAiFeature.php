<?php

namespace App\Jobs;

use App\Ai\AiException;
use App\Ai\Features\DocumentCheck;
use App\Ai\Features\VendorEmail;
use App\Ai\Features\WhatsappRfq;
use App\Events\CrmEvent;
use App\Models\AiRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Runs one ai_runs row. 3 tries, then the user is told it failed; manual screens keep working. */
class RunAiFeature implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public const HANDLERS = ['whatsapp_rfq' => WhatsappRfq::class, 'vendor_email' => VendorEmail::class, 'doc_check' => DocumentCheck::class];

    public function __construct(public int $runId) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $run = AiRun::find($this->runId);
        if (! $run || in_array($run->status, ['done', 'accepted', 'rejected'], true)) {
            return;
        }
        try {
            app(self::HANDLERS[$run->feature])->handle($run);
        } catch (AiException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                throw $e;
            }
            $this->failed($e); // not worth retrying: record it and tell the user; manual screens keep working
        }
    }

    public function failed(Throwable $e): void
    {
        $run = AiRun::find($this->runId);
        if (! $run) {
            return;
        }
        $run->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
        event(new CrmEvent('ai_failed', ['feature' => __('ai.f_'.$run->feature), 'error' => $e instanceof AiException ? $e->getMessage() : __('ai.generic_error')], $run->file_id ? route('files.show', $run->file_id) : route('dashboard'), [], array_filter([$run->user_id]), null, false));
    }
}
