<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Events\FileStageChanged;
use App\Exceptions\WorkflowException;
use App\Models\FileHistory;
use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only place a file's stage, checklist and status change.
 * Every action runs in a transaction on a row locked for update.
 */
class FileStageService
{
    public const LOST_CATEGORIES = ['price', 'transit_time', 'competitor', 'cancelled', 'no_reply', 'other'];

    public function tick(ShipmentFile $file, string $item, bool $value, User $user): ShipmentFile
    {
        return $this->locked($file, function (ShipmentFile $f) use ($item, $value, $user) {
            $stage = Workflow::stageOf($item) ?? throw WorkflowException::one('workflow.unknown_item');
            $this->assertOpen($f);
            if (! $user->isManagement()) {
                if (! $user->hasRole(Workflow::itemOwner($item))) {
                    throw WorkflowException::one('workflow.only_owner_tick', ['role' => __('roles.'.Workflow::itemOwner($item))]);
                }
                if ($stage !== $f->stage) {
                    throw WorkflowException::one('workflow.not_current_stage');
                }
            }
            $checks = $f->checks ?? [];
            if ((bool) ($checks[$item] ?? false) === $value) {
                return $f;
            }
            $checks[$item] = $value;
            $f->forceStageAttributes(['checks' => $checks]);
            $f->save();
            $this->history($f, $user, $value ? 'ticked' : 'unticked', null, null, $item);
            return $f;
        });
    }

    /** Problems blocking the move to the next stage (translated strings). */
    public function gates(ShipmentFile $f): array
    {
        $p = [];
        if ($f->status !== 'open') {
            return [__('workflow.not_open')];
        }
        foreach (Workflow::items($f->stage) as $item => [$required]) {
            if ($required && ! $f->check($item)) {
                $p[] = __('workflow.need_check', ['item' => __('checks.'.$item)]);
            }
        }
        switch ($f->stage) {
            case 1:
                if (InquiryValidator::forFile($f, true)) {
                    $p[] = __('workflow.need_rfq');
                }
                break;
            case 2:
                $terms = $f->payTerms;
                if ($terms && $terms->status === 'pending') {
                    $p[] = __('workflow.need_pay_terms');
                }
                if ($f->quoteLines()->exists() && ! $f->margin_approved_at && app(MarginService::class)->belowMinimum($f)) {
                    $p[] = __('workflow.need_margin_approval');
                }
                break;
            case 3:
                $needsBooking = $f->carrier || ($f->ship['carrier'] ?? null) || in_array($f->service, ['fcl', 'lcl', 'air', 'land', 'roro', 'se', 'bulk'], true) || $f->hasAddon('trucking');
                if ($needsBooking && ! $f->check('booking_done')) {
                    $p[] = __('workflow.need_check', ['item' => __('checks.booking_done')]);
                }
                break;
            case 4:
                if (! $f->bl_no) {
                    $p[] = __('workflow.need_bl');
                }
                if (! $f->atd) {
                    $p[] = __('workflow.need_atd');
                }
                if (! $f->eta) {
                    $p[] = __('workflow.need_eta');
                }
                if (! $f->carrier) {
                    $p[] = __('workflow.need_carrier');
                }
                if ($n = $f->openBlockers()) {
                    $p[] = __('workflow.need_blockers', ['n' => $n]);
                }
                break;
        }
        return $p;
    }

    public function canMove(ShipmentFile $f, User $user): bool
    {
        return $user->isManagement() || $user->hasRole(Workflow::owner($f->stage));
    }

    public function advance(ShipmentFile $file, User $user): ShipmentFile
    {
        return $this->locked($file, function (ShipmentFile $f) use ($user) {
            $this->assertOpen($f);
            if (! $this->canMove($f, $user)) {
                throw WorkflowException::one('workflow.not_owner', ['role' => __('roles.'.Workflow::owner($f->stage))]);
            }
            if ($p = $this->gates($f)) {
                throw new WorkflowException($p);
            }
            $from = $f->stage;
            if ($from === 7) {
                $f->forceStageAttributes(['status' => 'closed', 'closed_at' => now()]);
                $f->save();
                $this->history($f, $user, 'closed', 7, 7);
                FileStageChanged::dispatch($f, 7, 7, 'closed', $user);
                return $f;
            }
            $f->forceStageAttributes(['stage' => $from + 1, 'stage_entered_at' => now()]);
            if ($from === 2) {
                $f->pricing_status = 'won'; // client approved in writing
            }
            $f->save();
            $this->history($f, $user, 'moved', $from, $from + 1);
            FileStageChanged::dispatch($f, $from, $from + 1, 'moved', $user);
            return $f;
        });
    }

    public function back(ShipmentFile $file, User $user, string $reason): ShipmentFile
    {
        return $this->locked($file, function (ShipmentFile $f) use ($user, $reason) {
            if (! $user->isManagement()) {
                throw WorkflowException::one('workflow.only_management');
            }
            $this->assertOpen($f);
            if ($f->stage <= 1) {
                throw WorkflowException::one('workflow.already_first');
            }
            $from = $f->stage;
            $f->forceStageAttributes(['stage' => $from - 1, 'stage_entered_at' => now()]);
            $f->save();
            $this->history($f, $user, 'moved_back', $from, $from - 1, $reason);
            FileStageChanged::dispatch($f, $from, $from - 1, 'moved_back', $user);
            return $f;
        });
    }

    public function markLost(ShipmentFile $file, User $user, string $category, string $reason): ShipmentFile
    {
        return $this->locked($file, function (ShipmentFile $f) use ($user, $category, $reason) {
            $this->assertOpen($f);
            if (! $user->isManagement() && ! ($user->hasRole('sales') && $f->sales_id === $user->id)) {
                throw WorkflowException::one('workflow.only_sales_lost');
            }
            if (! in_array($category, self::LOST_CATEGORIES, true) || trim($reason) === '') {
                throw WorkflowException::one('workflow.lost_reason_required');
            }
            $f->forceStageAttributes(['status' => 'lost', 'lost_stage' => $f->stage, 'lost_category' => $category, 'lost_reason' => trim($reason)]);
            $f->save();
            $f->pricing_status = 'lost';
            $f->save();
            $this->history($f, $user, 'lost', $f->stage, null, __('lost.'.$category).': '.$reason);
            FileStageChanged::dispatch($f, $f->stage, null, 'lost', $user);
            return $f;
        });
    }

    public function reopen(ShipmentFile $file, User $user): ShipmentFile
    {
        return $this->locked($file, function (ShipmentFile $f) use ($user) {
            if (! $user->isManagement()) {
                throw WorkflowException::one('workflow.only_management');
            }
            $f->forceStageAttributes(['status' => 'open', 'lost_stage' => null, 'lost_category' => null, 'lost_reason' => null, 'closed_at' => null]);
            $f->save();
            $this->history($f, $user, 'reopened', null, $f->stage);
            return $f;
        });
    }

    /** Creates a brand-new file at stage 1 (used by the RFQ service). */
    public function initialize(ShipmentFile $f, User $user): ShipmentFile
    {
        ShipmentFile::withStageWrite(function () use ($f) {
            $f->forceStageAttributes(['stage' => 1, 'status' => 'open', 'checks' => ['first_contact' => true], 'stage_entered_at' => now()]);
            $f->save();
        });
        $this->history($f, $user, 'created', null, 1);
        return $f;
    }

    private function locked(ShipmentFile $file, callable $fn): ShipmentFile
    {
        $result = DB::transaction(function () use ($file, $fn) {
            $fresh = ShipmentFile::withoutGlobalScopes()->whereKey($file->getKey())->lockForUpdate()->firstOrFail();
            return ShipmentFile::withStageWrite(fn () => $fn($fresh));
        });
        $file->setRawAttributes($result->getAttributes(), true);
        return $file;
    }

    private function assertOpen(ShipmentFile $f): void
    {
        if ($f->status !== 'open') {
            throw WorkflowException::one('workflow.not_open');
        }
    }

    public function history(ShipmentFile $f, ?User $user, string $action, ?int $from = null, ?int $to = null, ?string $note = null): void
    {
        FileHistory::create(['file_id' => $f->id, 'user_id' => $user?->id, 'action' => $action, 'from_stage' => $from, 'to_stage' => $to, 'note' => $note]);
    }
}
