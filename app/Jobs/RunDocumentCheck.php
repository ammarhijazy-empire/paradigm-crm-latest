<?php

namespace App\Jobs;

use App\Ai\AiGuard;
use App\Models\AiRun;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Debounced: several uploads in a row produce one check. */
class RunDocumentCheck implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(public int $fileId, public ?int $userId) {}

    public function uniqueId(): string
    {
        return 'doccheck-'.$this->fileId;
    }

    public function handle(AiGuard $guard): void
    {
        if (! $guard->enabled('doc_check') || ! app(\App\Ai\AiClient::class)->configured()) {
            return;
        }
        $run = AiRun::create(['feature' => 'doc_check', 'user_id' => $this->userId, 'file_id' => $this->fileId, 'status' => 'queued', 'input_ref' => ['file_id' => $this->fileId]]);
        RunAiFeature::dispatchSync($run->id);
    }
}
