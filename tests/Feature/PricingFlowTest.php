<?php

namespace Tests\Feature;

use App\Domain\PricingService;
use App\Domain\QuotationService;
use App\Exceptions\WorkflowException;
use App\Models\FilePayTerm;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PricingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $name = 'V'): Vendor
    {
        return Vendor::create(['name' => $name, 'type' => 'agent', 'email' => strtolower($name).'@v.example', 'active' => true]);
    }

    #[Test]
    public function quotes_are_ranked_by_usd_total_then_transit_and_expired_ones_go_last(): void
    {
        $f = $this->fileFor(stage: 2);
        $p = app(PricingService::class);
        $pr = $this->user('pricing');
        $p->recordQuote($f, ['vendor_id' => $this->vendor('A')->id, 'currency' => 'USD', 'freight' => 3000, 'transit_days' => 20, 'valid_until' => now()->addWeek()->toDateString()], $pr);
        $p->recordQuote($f, ['vendor_id' => $this->vendor('B')->id, 'currency' => 'EUR', 'freight' => 2000, 'transit_days' => 30, 'valid_until' => now()->addWeek()->toDateString()], $pr); // ≈ $2,171
        $p->recordQuote($f, ['vendor_id' => $this->vendor('C')->id, 'currency' => 'USD', 'freight' => 1000, 'transit_days' => 25, 'valid_until' => now()->subDay()->toDateString()], $pr);
        $r = $p->ranked($f);
        $this->assertSame(['B', 'A', 'C'], $r->pluck('vendor.name')->all());
        $this->assertSame([1, 2, null], $r->pluck('rank')->all());
        $this->assertContains(__('pricing.flag_expired'), $r[2]->flags);
    }

    #[Test]
    public function full_pricing_path_review_return_needs_a_reason(): void
    {
        $f = $this->fileFor(stage: 2);
        $p = app(PricingService::class);
        $pricing = $this->user('pricing');
        $q = $p->recordQuote($f, ['vendor_id' => $this->vendor()->id, 'currency' => 'USD', 'freight' => 1800, 'transit_days' => 28], $pricing);
        $p->choose($f, $q, $pricing);
        $this->assertSame(1800.0, (float) $f->costLines()->where('type', 'freight')->value('estimated'));
        try {
            $p->submitForReview($f->refresh(), $pricing);
            $this->fail('needs ETD/TT/carrier');
        } catch (WorkflowException) {
        }
        $p->saveQuoteMeta($f, ['quote_etd' => now()->addWeek()->toDateString(), 'quote_transit_days' => 28, 'quote_carrier' => 'COSCO'], $pricing);
        $p->submitForReview($f->refresh(), $pricing);
        $reviewer = $this->user('pricing_review');
        $this->actingAs($reviewer)->post("/files/{$f->number}/review/decide", ['decision' => 'return'])->assertSessionHasErrors('workflow');
        $this->actingAs($reviewer)->post("/files/{$f->number}/review/decide", ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame('priced', $f->fresh()->pricing_status);
    }

    #[Test]
    public function sales_cannot_do_pricing_and_pricing_cannot_quote(): void
    {
        $f = $this->fileFor(stage: 2);
        $this->actingAs($f->sales)->post("/files/{$f->number}/quotes", ['vendor_id' => $this->vendor()->id, 'currency' => 'USD', 'freight' => 1])->assertStatus(403);
        $this->actingAs($this->user('pricing'))->put("/files/{$f->number}/quote-lines", ['lines' => []])->assertStatus(403);
    }

    #[Test]
    public function credit_terms_need_accounts_before_the_quotation_can_be_sent(): void
    {
        $f = $this->fileFor(stage: 2, attrs: ['pricing_status' => 'priced']);
        FilePayTerm::create(['file_id' => $f->id, 'method' => 'credit', 'credit_days' => 30, 'status' => 'pending']);
        $f->costLines()->create(['type' => 'freight', 'currency' => 'USD', 'estimated' => 1000, 'exchange_rate' => 1]);
        $q = app(QuotationService::class);
        $q->saveLines($f, [['section' => 'freight', 'description_en' => 'Freight', 'description_ar' => 'نولون', 'currency' => 'USD', 'amount' => 1500]], $f->sales);
        $this->assertContains(__('workflow.need_pay_terms'), $q->canSend($f->refresh()));
        $this->actingAs($f->sales)->post("/files/{$f->number}/pay-terms", ['decision' => 'approve'])->assertStatus(403);
        $this->actingAs($this->user('accounts'))->post("/files/{$f->number}/pay-terms", ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame([], $q->canSend($f->refresh()));
    }

    #[Test]
    public function margin_below_minimum_needs_management_approval(): void
    {
        $f = $this->fileFor(stage: 2, attrs: ['pricing_status' => 'priced']);
        $f->costLines()->create(['type' => 'freight', 'currency' => 'USD', 'estimated' => 1000, 'exchange_rate' => 1]);
        $q = app(QuotationService::class);
        $q->saveLines($f, [['section' => 'freight', 'description_en' => 'Freight', 'description_ar' => 'نولون', 'currency' => 'USD', 'amount' => 1150]], $f->sales); // $150 < $200
        $this->assertContains(__('workflow.need_margin_approval'), $q->canSend($f->refresh()));
        $this->actingAs($f->sales)->post("/files/{$f->number}/margin-approve")->assertStatus(403);
        $this->actingAs($this->user('admin'))->post("/files/{$f->number}/margin-approve")->assertSessionHasNoErrors();
        $this->assertSame([], $q->canSend($f->refresh()));
        // a new price clears the approval
        $q->saveLines($f, [['section' => 'freight', 'description_en' => 'Freight', 'description_ar' => 'نولون', 'currency' => 'USD', 'amount' => 1100]], $f->sales);
        $this->assertNull($f->fresh()->margin_approved_at);
    }

    #[Test]
    public function negotiation_is_limited_to_three_rounds(): void
    {
        $f = $this->fileFor(stage: 2, attrs: ['pricing_status' => 'quoted']);
        $q = app(QuotationService::class);
        foreach (range(1, 3) as $i) {
            $q->addRound($f, $f->sales, 1000, 'countered', null);
        }
        $this->expectException(WorkflowException::class);
        $q->addRound($f, $f->sales, 900, 'countered', null);
    }
}
