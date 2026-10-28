<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Exceptions\WorkflowException;
use App\Mail\OutboxMailer;
use App\Models\CostLine;
use App\Models\CostReview;
use App\Models\RepriceRequest;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorQuote;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PricingService
{
    public function __construct(private FileStageService $stages, private OutboxMailer $mailer) {}

    /** Email the request to chosen vendors. Contact details of the client are never included. */
    public function sendRfq(ShipmentFile $f, array $vendorIds, User $user, ?string $extraNote = null): int
    {
        $this->assertPricing($user);
        $vendors = Vendor::whereIn('id', $vendorIds)->whereNotNull('email')->where('active', true)->get();
        if ($vendors->isEmpty()) {
            throw WorkflowException::one('pricing.no_vendors');
        }
        foreach ($vendors as $v) {
            $this->mailer->queue(
                to: $v->email,
                subject: "{$f->ref}-OPS ".__('pricing.rfq_subject', [], 'en').' '.$f->origin.' → '.$f->destination,
                view: 'emails.rfq-vendor',
                data: ['file' => $f, 'vendor' => $v, 'note' => $extraNote],
                kind: 'rfq_vendor',
                related: $f,
                user: $user,
                locale: 'en',
            );
        }
        if ($f->pricing_status === 'draft') {
            $f->update(['pricing_status' => 'pending']);
        }
        if (! $f->check('rates_requested') && $f->stage === 2) {
            $this->stages->tick($f, 'rates_requested', true, $user);
        }
        $this->stages->history($f, $user, 'rfq_sent', null, null, $vendors->pluck('name')->join(', '));
        return $vendors->count();
    }

    public function recordQuote(ShipmentFile $f, array $d, ?User $user, string $source = 'manual', ?int $inboundId = null, string $status = 'confirmed'): VendorQuote
    {
        return DB::transaction(function () use ($f, $d, $user, $source, $inboundId, $status) {
            $sur = collect($d['surcharges'] ?? [])->filter(fn ($s) => ($s['name'] ?? '') !== '' && is_numeric($s['amount'] ?? null))->values()->all();
            $total = (float) ($d['freight'] ?? 0) + collect($sur)->sum('amount') + (float) ($d['origin_charges'] ?? 0) + (float) ($d['destination_charges'] ?? 0);
            return VendorQuote::create([
                'file_id' => $f->id, 'vendor_id' => $d['vendor_id'], 'ref' => RefGenerator::quoteRef($f->ref),
                'carrier' => $d['carrier'] ?? null, 'routing' => $d['routing'] ?? null, 'currency' => $d['currency'] ?? 'USD',
                'freight' => $d['freight'] ?? 0, 'surcharges' => $sur, 'origin_charges' => $d['origin_charges'] ?? 0,
                'destination_charges' => $d['destination_charges'] ?? 0, 'total' => round($total, 2),
                'transit_days' => $d['transit_days'] ?? null, 'free_days' => $d['free_days'] ?? null,
                'valid_until' => $d['valid_until'] ?? null, 'etd' => $d['etd'] ?? null, 'conditions' => $d['conditions'] ?? null,
                'status' => $status, 'source' => $source, 'ai_confidence' => $d['confidence'] ?? null,
                'inbound_email_id' => $inboundId, 'created_by' => $user?->id,
            ]);
        });
    }

    public function confirmQuote(VendorQuote $q, array $d, User $user): VendorQuote
    {
        $this->assertPricing($user);
        $sur = collect($d['surcharges'] ?? $q->surcharges ?? [])->values()->all();
        $q->fill(collect($d)->only(['carrier', 'routing', 'currency', 'freight', 'origin_charges', 'destination_charges', 'transit_days', 'free_days', 'valid_until', 'etd', 'conditions'])->all());
        $q->surcharges = $sur;
        $q->total = round((float) $q->freight + collect($sur)->sum('amount') + (float) $q->origin_charges + (float) $q->destination_charges, 2);
        $q->status = 'confirmed';
        $q->save();
        return $q;
    }

    /** Confirmed quotes ranked: total USD (today's rates), then transit time, free time, validity. */
    public function ranked(ShipmentFile $f): Collection
    {
        $today = Carbon::today();
        return $f->vendorQuotes()->with('vendor')->get()->map(function (VendorQuote $q) use ($today) {
            $q->total_usd = Money::toUsd($q->total, $q->currency);
            $q->expired = $q->valid_until && $q->valid_until->lt($today);
            $q->rankable = $q->status === 'confirmed' && ! $q->expired;
            $q->flags = array_values(array_filter([
                $q->expired ? __('pricing.flag_expired') : null,
                ! (float) $q->destination_charges && ! collect($q->surcharges)->contains(fn ($s) => stripos($s['name'] ?? '', 'dest') !== false) ? __('pricing.flag_no_dest') : null,
                ! $q->transit_days ? __('pricing.flag_no_tt') : null,
                ! $q->valid_until ? __('pricing.flag_no_validity') : null,
            ]));
            return $q;
        })->sort(function ($a, $b) {
            return [(int) ! $a->rankable, $a->total_usd, $a->transit_days ?? 999, -($a->free_days ?? 0), -($a->valid_until?->timestamp ?? 0)]
                <=> [(int) ! $b->rankable, $b->total_usd, $b->transit_days ?? 999, -($b->free_days ?? 0), -($b->valid_until?->timestamp ?? 0)];
        })->values()->each(function ($q, $i) {
            $q->rank = $q->rankable ? $i + 1 : null;
        });
    }

    public function choose(ShipmentFile $f, VendorQuote $q, User $user): void
    {
        $this->assertPricing($user);
        abort_unless($q->file_id === $f->id && $q->status === 'confirmed', 422);
        DB::transaction(function () use ($f, $q, $user) {
            $f->vendorQuotes()->update(['is_chosen' => false]);
            $q->update(['is_chosen' => true]);
            $rate = Money::rate($q->currency);
            CostLine::updateOrCreate(
                ['file_id' => $f->id, 'type' => 'freight', 'extra_fee' => false],
                ['vendor_id' => $q->vendor_id, 'currency' => $q->currency, 'exchange_rate' => $rate, 'estimated' => (float) $q->freight + collect($q->surcharges)->sum('amount'), 'description' => $q->ref]
            );
            foreach (['origin' => 'origin_charges', 'destination' => 'destination_charges'] as $type => $col) {
                if ((float) $q->{$col} > 0) {
                    CostLine::updateOrCreate(['file_id' => $f->id, 'type' => $type, 'extra_fee' => false], ['vendor_id' => $q->vendor_id, 'currency' => $q->currency, 'exchange_rate' => $rate, 'estimated' => $q->{$col}, 'description' => $q->ref]);
                }
            }
            $f->update(['chosen_vendor_quote_id' => $q->id, 'quote_etd' => $q->etd, 'quote_transit_days' => $q->transit_days, 'quote_carrier' => $q->carrier]);
            $this->stages->history($f, $user, 'cost_chosen', null, null, $q->ref);
        });
    }

    public function saveCostLines(ShipmentFile $f, array $lines, User $user, bool $actual = false): void
    {
        if ($actual) {
            abort_unless($user->actsAs('accounts') || $user->actsAs('ops'), 403);
        } else {
            $this->assertPricing($user);
        }
        DB::transaction(function () use ($f, $lines, $actual) {
            foreach ($lines as $l) {
                $line = ! empty($l['id']) ? CostLine::where('file_id', $f->id)->findOrFail($l['id']) : new CostLine(['file_id' => $f->id]);
                if (! empty($l['_delete'])) {
                    $actual ?: $line->exists && $line->delete();
                    continue;
                }
                if ($actual) {
                    $line->fill(['actual' => $l['actual'] ?? null, 'vendor_due_date' => $l['vendor_due_date'] ?? $line->vendor_due_date]);
                    if (! $line->exists) {
                        $line->fill(['type' => $l['type'], 'currency' => $l['currency'], 'vendor_id' => $l['vendor_id'] ?? null, 'estimated' => 0, 'extra_fee' => true, 'description' => $l['description'] ?? null, 'exchange_rate' => Money::rate($l['currency'])]);
                    }
                } else {
                    $line->fill([
                        'type' => $l['type'], 'description' => $l['description'] ?? null, 'vendor_id' => $l['vendor_id'] ?? null,
                        'currency' => $l['currency'], 'estimated' => $l['estimated'] ?? 0, 'exchange_rate' => Money::rate($l['currency']),
                    ]);
                }
                $line->save();
            }
        });
    }

    public function saveQuoteMeta(ShipmentFile $f, array $d, User $user): void
    {
        $this->assertPricing($user);
        $f->update(['quote_etd' => $d['quote_etd'] ?? null, 'quote_transit_days' => $d['quote_transit_days'] ?? null, 'quote_carrier' => $d['quote_carrier'] ?? null]);
    }

    public function submitForReview(ShipmentFile $f, User $user): CostReview
    {
        $this->assertPricing($user);
        if (! $f->costLines()->exists()) {
            throw WorkflowException::one('pricing.need_costs');
        }
        if (! $f->quote_etd || ! $f->quote_transit_days || ! $f->quote_carrier) {
            throw WorkflowException::one('pricing.need_quote_meta');
        }
        $r = CostReview::create(['file_id' => $f->id, 'requested_by' => $user->id, 'status' => 'pending']);
        $f->update(['pricing_status' => 'review']);
        $this->stages->history($f, $user, 'sent_for_review');
        event(new CrmEvent('review_needed', ['ref' => $f->ref], route('files.show', $f), ['pricing_review'], [], $user));
        return $r;
    }

    public function review(ShipmentFile $f, User $user, bool $approve, ?string $reason): void
    {
        abort_unless($user->actsAs('pricing_review'), 403);
        if (! $approve && ! trim((string) $reason)) {
            throw WorkflowException::one('pricing.reason_required');
        }
        $r = $f->costReviews()->where('status', 'pending')->first() ?? throw WorkflowException::one('pricing.no_pending_review');
        $r->update(['status' => $approve ? 'approved' : 'returned', 'reviewer_id' => $user->id, 'reason' => $reason, 'decided_at' => now()]);
        $f->update(['pricing_status' => $approve ? 'priced' : 'pending']);
        $this->stages->history($f, $user, $approve ? 'cost_approved' : 'cost_returned', null, null, $reason);
        if ($approve) {
            event(new CrmEvent('cost_ready', ['ref' => $f->ref], route('files.show', $f), [], [$f->sales_id], $user));
            $this->mailer->queue(to: $f->sales->email, subject: __('pricing.cost_ready_subject', ['ref' => $f->ref], $f->sales->locale), view: 'emails.costing-sales', data: ['file' => $f], kind: 'costing_sales', related: $f, user: $user, locale: $f->sales->locale);
        } else {
            event(new CrmEvent('cost_returned', ['ref' => $f->ref, 'reason' => $reason], route('files.show', $f), ['pricing'], [], $user));
        }
    }

    public function decidePayTerms(ShipmentFile $f, User $user, bool $approve, ?string $note): void
    {
        abort_unless($user->actsAs('accounts'), 403);
        $t = $f->payTerms ?? throw WorkflowException::one('pricing.no_pay_terms');
        $t->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $user->id, 'decided_at' => now(), 'note' => $note]);
        $this->stages->history($f, $user, $approve ? 'pay_terms_approved' : 'pay_terms_rejected', null, null, $note);
        event(new CrmEvent($approve ? 'pay_terms_approved' : 'pay_terms_rejected', ['ref' => $f->ref], route('files.show', $f), [], [$f->sales_id], $user));
    }

    public function requestReprice(ShipmentFile $f, User $user, string $reason, ?float $target): RepriceRequest
    {
        abort_unless($user->actsAs('sales'), 403);
        $r = RepriceRequest::create(['file_id' => $f->id, 'requested_by' => $user->id, 'reason' => $reason, 'target_usd' => $target]);
        $f->update(['pricing_status' => 'pending']);
        $this->stages->history($f, $user, 'reprice_requested', null, null, $reason);
        event(new CrmEvent('reprice_requested', ['ref' => $f->ref], route('files.show', $f), ['pricing'], [], $user));
        return $r;
    }

    public function answerReprice(RepriceRequest $r, User $user, string $answer, bool $declined = false): void
    {
        $this->assertPricing($user);
        $r->update(['status' => $declined ? 'declined' : 'answered', 'answered_by' => $user->id, 'answer' => $answer]);
        event(new CrmEvent('reprice_answered', ['ref' => $r->file->ref], route('files.show', $r->file), [], [$r->requested_by], $user));
    }

    private function assertPricing(User $user): void
    {
        abort_unless($user->actsAs('pricing'), 403);
    }
}
