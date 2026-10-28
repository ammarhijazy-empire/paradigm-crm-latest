<?php

namespace Tests\Feature;

use App\Domain\InvoiceService;
use App\Domain\MarginService;
use App\Exceptions\WorkflowException;
use App\Models\QuoteLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    use RefreshDatabase;

    private function billableFile()
    {
        $f = $this->fileFor(stage: 6);
        QuoteLine::create(['file_id' => $f->id, 'section' => 'freight', 'description_en' => 'Freight', 'description_ar' => 'نولون', 'currency' => 'USD', 'amount' => 1000]);
        QuoteLine::create(['file_id' => $f->id, 'section' => 'destination', 'description_en' => 'THC', 'description_ar' => 'تداول', 'currency' => 'USD', 'amount' => 200]);
        QuoteLine::create(['file_id' => $f->id, 'section' => 'clearance', 'description_en' => 'Clearance', 'description_ar' => 'تخليص', 'currency' => 'EGP', 'amount' => 10000]);
        return $f;
    }

    #[Test]
    public function one_invoice_per_currency_with_vat_per_line(): void
    {
        $f = $this->billableFile();
        $freightLine = $f->quoteLines()->where('amount', 1000)->first();
        $inv = app(InvoiceService::class)->createFromFile($f, $this->user('accounts'), [$freightLine->id => false]);
        $this->assertCount(2, $inv);
        $usd = collect($inv)->firstWhere('currency', 'USD');
        $this->assertEquals(1200, (float) $usd->subtotal);
        $this->assertEquals(28, (float) $usd->vat_total);        // 14% of 200 only
        $this->assertEquals(1228, (float) $usd->total);
        $egp = collect($inv)->firstWhere('currency', 'EGP');
        $this->assertEquals(11400, (float) $egp->total);
        $this->assertTrue($f->fresh()->check('final_invoice'));
        // running again creates nothing new
        $this->assertCount(0, app(InvoiceService::class)->createFromFile($f, $this->user('accounts'), []));
    }

    #[Test]
    public function payments_cannot_exceed_the_balance_and_full_payment_ticks_collection(): void
    {
        $f = $this->fileFor(stage: 6);
        QuoteLine::create(['file_id' => $f->id, 'section' => 'freight', 'description_en' => 'Freight', 'description_ar' => 'نولون', 'currency' => 'USD', 'amount' => 500]);
        $acc = $this->user('accounts');
        [$inv] = app(InvoiceService::class)->createFromFile($f, $acc, []);
        try {
            app(InvoiceService::class)->recordPayment($inv, $acc, 999, now()->toDateString(), 'bank', null);
            $this->fail('overpay accepted');
        } catch (WorkflowException) {
        }
        app(InvoiceService::class)->recordPayment($inv, $acc, 300, now()->toDateString(), 'bank', null);
        $this->assertSame('partly_paid', $inv->fresh()->status);
        app(InvoiceService::class)->recordPayment($inv->fresh(), $acc, 270, now()->toDateString(), 'bank', null);
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertTrue($f->fresh()->check('payment_collected'));
    }

    #[Test]
    public function only_accounts_or_management_issue_invoices_and_record_payments(): void
    {
        $f = $this->billableFile();
        $this->actingAs($f->sales)->post("/files/{$f->number}/invoices")->assertStatus(403);
        $this->actingAs($this->user('ops'))->post("/files/{$f->number}/invoices")->assertStatus(403);
        $this->actingAs($this->user('admin'))->post("/files/{$f->number}/invoices")->assertSessionHasNoErrors();
        $inv = $f->invoices()->first();
        $this->actingAs($f->sales)->post("/invoices/{$inv->number}/payments", ['amount' => 10, 'paid_at' => now()->toDateString()])->assertStatus(403);
        $this->actingAs($f->sales)->get("/invoices/{$inv->number}")->assertOk(); // read-only for the file's salesperson
    }

    #[Test]
    public function actual_profit_is_invoiced_minus_actual_costs_including_extra_fees(): void
    {
        $f = $this->billableFile();
        $f->costLines()->create(['type' => 'freight', 'currency' => 'USD', 'estimated' => 700, 'actual' => 720, 'exchange_rate' => 1]);
        $f->costLines()->create(['type' => 'storage', 'currency' => 'USD', 'estimated' => 0, 'actual' => 80, 'extra_fee' => true, 'exchange_rate' => 1]);
        app(InvoiceService::class)->createFromFile($f, $this->user('accounts'), []);
        $egpUsd = round(10000 / 49.35, 2);
        $this->assertEqualsWithDelta(1200 + $egpUsd - 800, app(MarginService::class)->actualProfitUsd($f->fresh()), 0.02);
    }

    #[Test]
    public function the_invoice_pdf_renders_in_both_languages(): void
    {
        $f = $this->billableFile();
        $acc = $this->user('accounts');
        [$inv] = app(InvoiceService::class)->createFromFile($f, $acc, []);
        foreach (['ar', 'en'] as $l) {
            $r = $this->actingAs($acc)->get("/invoices/{$inv->number}/pdf?lang={$l}")->assertOk();
            $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $r->getContent());
        }
    }
}
