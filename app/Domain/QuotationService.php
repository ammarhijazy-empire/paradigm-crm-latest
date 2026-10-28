<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Exceptions\WorkflowException;
use App\Models\QuoteLine;
use App\Models\QuoteRound;
use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public const MAX_ROUNDS = 3;

    public function __construct(private FileStageService $stages, private MarginService $margin) {}

    public function saveLines(ShipmentFile $f, array $lines, User $user): void
    {
        $this->assertSalesOwner($f, $user);
        if (! in_array($f->pricing_status, ['priced', 'quoted', 'won'], true) && ! $user->isManagement()) {
            throw WorkflowException::one('quote.cost_not_ready');
        }
        DB::transaction(function () use ($f, $lines) {
            $f->quoteLines()->delete();
            foreach (array_values($lines) as $i => $l) {
                if (trim((string) ($l['description_en'] ?? '')) === '' && trim((string) ($l['description_ar'] ?? '')) === '') {
                    continue;
                }
                QuoteLine::create([
                    'file_id' => $f->id, 'section' => $l['section'] ?? 'freight',
                    'description_en' => $l['description_en'] ?: $l['description_ar'], 'description_ar' => $l['description_ar'] ?: $l['description_en'],
                    'currency' => $l['currency'] ?? 'USD', 'amount' => $l['amount'] ?? 0, 'sort' => $i,
                ]);
            }
            // A new price needs a new approval if it falls below the minimum.
            $f->forceFill(['margin_approved_by' => null, 'margin_approved_at' => null])->save();
        });
        $f->load('quoteLines', 'costLines');
        if ($this->margin->belowMinimum($f)) {
            event(new CrmEvent('margin_approval_needed', ['ref' => $f->ref, 'margin' => number_format($this->margin->marginUsd($f), 2)], route('files.show', $f), [], [], $user));
        }
    }

    public function approveMargin(ShipmentFile $f, User $user): void
    {
        abort_unless($user->isManagement(), 403);
        $f->forceFill(['margin_approved_by' => $user->id, 'margin_approved_at' => now()])->save();
        $this->stages->history($f, $user, 'margin_approved', null, null, number_format($this->margin->marginUsd($f->load('quoteLines', 'costLines')), 2).' USD');
        event(new CrmEvent('margin_approved', ['ref' => $f->ref], route('files.show', $f), [], [$f->sales_id], $user, false));
    }

    public function canSend(ShipmentFile $f): array
    {
        $p = [];
        $f->loadMissing('quoteLines', 'costLines', 'payTerms');
        if ($f->quoteLines->isEmpty()) {
            $p[] = __('quote.need_lines');
        }
        if ($f->payTerms && $f->payTerms->status === 'pending') {
            $p[] = __('workflow.need_pay_terms');
        }
        if ($f->payTerms && $f->payTerms->status === 'rejected') {
            $p[] = __('quote.pay_terms_rejected');
        }
        if ($this->margin->belowMinimum($f) && ! $f->margin_approved_at) {
            $p[] = __('workflow.need_margin_approval');
        }
        return $p;
    }

    public function markSent(ShipmentFile $f, User $user, string $locale): void
    {
        $this->assertSalesOwner($f, $user);
        if ($p = $this->canSend($f)) {
            throw new WorkflowException($p);
        }
        $f->update(['pricing_status' => 'quoted']);
        $round = $f->quoteRounds()->max('round') ?? 0;
        if ($round === 0) {
            $this->addRound($f, $user, null, 'pending', null);
        }
        if (! $f->check('quotation_sent') && $f->stage === 2) {
            $this->stages->tick($f, 'quotation_sent', true, $user);
        }
        $this->stages->history($f, $user, 'quotation_sent', null, null, strtoupper($locale));
    }

    public function addRound(ShipmentFile $f, User $user, ?float $counter, string $outcome, ?string $notes): QuoteRound
    {
        $this->assertSalesOwner($f, $user);
        $n = ($f->quoteRounds()->max('round') ?? 0) + 1;
        if ($n > self::MAX_ROUNDS) {
            throw WorkflowException::one('quote.max_rounds', ['n' => self::MAX_ROUNDS]);
        }
        return QuoteRound::create(['file_id' => $f->id, 'round' => $n, 'our_price_usd' => $this->margin->sellUsd($f->load('quoteLines')), 'counter_offer_usd' => $counter, 'outcome' => $outcome, 'notes' => $notes, 'user_id' => $user->id]);
    }

    public function updateRound(QuoteRound $r, User $user, ?float $counter, string $outcome, ?string $notes): void
    {
        $this->assertSalesOwner($r->file, $user);
        $r->update(['counter_offer_usd' => $counter, 'outcome' => $outcome, 'notes' => $notes]);
        if ($outcome === 'accepted') {
            $r->file->update(['pricing_status' => 'won']);
        }
    }

    private function assertSalesOwner(ShipmentFile $f, User $user): void
    {
        abort_unless($user->isManagement() || ($user->hasRole('sales') && $f->sales_id === $user->id), 403);
    }
}
