<?php

namespace App\Listeners;

use App\Domain\Workflow;
use App\Events\CrmEvent;
use App\Events\FileStageChanged;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\Notification;

/** All notifications are created here, on the server, from events fired by the services. */
class NotifyUsers
{
    public function handleCrm(CrmEvent $e): void
    {
        $q = User::where('active', true)->where(function ($q) use ($e) {
            $q->whereIn('id', $e->userIds ?: [0]);
            if ($e->roles) {
                $q->orWhereHas('roles', fn ($r) => $r->whereIn('name', $e->roles));
            }
            if ($e->includeManagement && $e->roles) {
                $q->orWhereHas('roles', fn ($r) => $r->where('name', 'admin'));
            }
        });
        if ($e->by) {
            $q->where('id', '!=', $e->by->id);
        }
        Notification::send($q->get(), new CrmNotification($e->type, $e->params, $e->url));
    }

    public function handleStage(FileStageChanged $e): void
    {
        $f = $e->file;
        if ($e->action === 'moved' && $e->to) {
            $role = Workflow::owner($e->to);
            $ids = $role === 'sales' ? [$f->sales_id] : ($role === 'ops' && $f->ops_id ? [$f->ops_id] : []);
            $this->handleCrm(new CrmEvent('stage_moved', ['ref' => $f->number, 'stage' => __('stages.'.$e->to)], route('files.show', $f), $ids ? [] : [$role], array_merge($ids, [$f->sales_id]), $e->by, false));
        } elseif ($e->action === 'lost') {
            $this->handleCrm(new CrmEvent('file_lost', ['ref' => $f->number, 'reason' => $f->lost_reason], route('files.show', $f), ['admin'], [], $e->by, false));
        } elseif ($e->action === 'closed') {
            $this->handleCrm(new CrmEvent('file_closed', ['ref' => $f->number], route('files.show', $f), ['admin'], [$f->sales_id], $e->by, false));
        }
    }

    public function subscribe($events): array
    {
        return [CrmEvent::class => 'handleCrm', FileStageChanged::class => 'handleStage'];
    }
}
