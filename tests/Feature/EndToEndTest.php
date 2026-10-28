<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ShipmentFile;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** One file all the way: request → pricing → review → quotation → approval → booking → shipping → customs → invoice → payment → after-sale → closed. */
class EndToEndTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_file_goes_from_request_to_closed_through_every_department(): void
    {
        $sales = $this->user('sales');
        $pricing = $this->user('pricing');
        $review = $this->user('pricing_review');
        $ops = $this->user('ops');
        $customs = $this->user('customs');
        $acc = $this->user('accounts');
        $client = Client::factory()->create(['owner_id' => $sales->id]);
        $vendor = Vendor::create(['name' => 'Agent', 'type' => 'agent', 'email' => 'a@agent.example', 'active' => true]);

        // 1. Sales sends the quote request
        $this->actingAs($sales)->post('/rfq', ['client_id' => $client->id, 'direction' => 'import', 'service' => 'fcl', 'addons' => ['clearance'], 'origin' => 'CNNGB', 'destination' => 'EGALY', 'incoterm' => 'FOB',
            'inq' => ['rows' => [['type' => "40'HC", 'count' => 1]], 'cargo' => 'general', 'commodity' => 'Granules', 'ready' => now()->addWeek()->toDateString(), 'free_days' => 14, 'freight_terms' => 'collect', 'pay' => 'credit', 'credit_days' => 30],
            'action' => 'send'])->assertRedirect();
        $f = ShipmentFile::firstOrFail();
        $n = $f->number;
        $this->assertSame(2, $f->stage);

        // 2. Pricing: RFQ to vendors, record and choose a quote, ETD/TT/carrier, send for review
        $this->actingAs($pricing)->post("/files/{$n}/rfq-vendors", ['vendor_ids' => [$vendor->id]])->assertSessionHasNoErrors();
        $this->actingAs($pricing)->post("/files/{$n}/quotes", ['vendor_id' => $vendor->id, 'currency' => 'USD', 'freight' => 1500, 'destination_charges' => 300, 'transit_days' => 28, 'valid_until' => now()->addMonth()->toDateString()])->assertSessionHasNoErrors();
        $q = $f->vendorQuotes()->first();
        $this->actingAs($pricing)->post("/files/{$n}/quotes/{$q->id}/choose")->assertSessionHasNoErrors();
        $this->actingAs($pricing)->put("/files/{$n}/quote-meta", ['quote_etd' => now()->addWeek()->toDateString(), 'quote_transit_days' => 28, 'quote_carrier' => 'COSCO']);
        $this->actingAs($pricing)->post("/files/{$n}/review")->assertSessionHasNoErrors();
        // 3. Review approves; accounts approves credit
        $this->actingAs($review)->post("/files/{$n}/review/decide", ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->actingAs($acc)->post("/files/{$n}/pay-terms", ['decision' => 'approve'])->assertSessionHasNoErrors();
        // 4. Sales prices, sends the quotation, gets written approval, moves to ops
        $this->actingAs($sales)->put("/files/{$n}/quote-lines", ['lines' => [
            ['section' => 'freight', 'description_en' => 'Ocean freight', 'description_ar' => 'نولون بحري', 'currency' => 'USD', 'amount' => 1800],
            ['section' => 'destination', 'description_en' => 'Destination charges', 'description_ar' => 'مصاريف الوصول', 'currency' => 'USD', 'amount' => 450],
        ]])->assertSessionHasNoErrors();
        $this->actingAs($sales)->get("/files/{$n}/quotation.pdf?lang=ar")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($sales)->post("/files/{$n}/quotation-sent", ['lang' => 'ar'])->assertSessionHasNoErrors();
        $this->actingAs($sales)->post("/files/{$n}/advance")->assertSessionHasErrors('workflow'); // no written approval yet
        $this->actingAs($sales)->post("/files/{$n}/checks", ['item' => 'client_approval', 'value' => 1]);
        $this->actingAs($sales)->post("/files/{$n}/advance")->assertSessionHasNoErrors();
        $this->assertSame(3, $f->fresh()->stage);
        // 5. Ops: handover, ACID, booking, shipped
        $this->actingAs($sales)->post("/files/{$n}/checks", ['item' => 'sales_handover', 'value' => 1]);
        $this->actingAs($ops)->post("/files/{$n}/checks", ['item' => 'acid_nafeza', 'value' => 1]);
        $this->actingAs($ops)->put("/files/{$n}/booking", ['carrier' => 'COSCO', 'booking_no' => 'BK1', 'acid_no' => '4551026100000001', 'etd' => now()->toDateString()]);
        $this->actingAs($ops)->post("/files/{$n}/checks", ['item' => 'booking_done', 'value' => 1]);
        $this->actingAs($ops)->post("/files/{$n}/advance")->assertSessionHasNoErrors();
        $this->actingAs($ops)->put("/files/{$n}/booking", ['carrier' => 'COSCO', 'bl_no' => 'COSU123', 'etd' => now()->toDateString(), 'atd' => now()->toDateString()]);
        $this->assertNotNull($f->fresh()->eta); // ETA = ATD + transit time
        $this->actingAs($ops)->post("/files/{$n}/advance")->assertSessionHasNoErrors();
        // 6. Customs + accounts release, delivery, actual costs
        foreach (['arrival_notice', 'do_collected', 'customs_cleared', 'delivered'] as $c) {
            $this->actingAs($customs)->post("/files/{$n}/checks", ['item' => $c, 'value' => 1]);
        }
        $this->actingAs($customs)->post("/files/{$n}/advance")->assertSessionHasErrors('workflow'); // release not approved
        $this->actingAs($acc)->post("/files/{$n}/checks", ['item' => 'release_approved', 'value' => 1]);
        $lines = $f->costLines()->get()->map(fn ($c) => ['id' => $c->id, 'actual' => (float) $c->estimated + 10])->all();
        $this->actingAs($ops)->put("/files/{$n}/actual-costs", ['lines' => $lines])->assertSessionHasNoErrors();
        $this->actingAs($ops)->post("/files/{$n}/checks", ['item' => 'actual_costs_handed', 'value' => 1]);
        $this->actingAs($customs)->post("/files/{$n}/advance")->assertSessionHasNoErrors();
        // 7. Accounts invoices and collects
        $this->actingAs($acc)->post("/files/{$n}/invoices")->assertSessionHasNoErrors();
        $inv = Invoice::firstOrFail();
        $this->assertEquals(round(2250 * 1.14, 2), (float) $inv->total);
        $this->actingAs($acc)->post("/invoices/{$inv->number}/payments", ['amount' => (float) $inv->total, 'paid_at' => now()->toDateString(), 'method' => 'bank'])->assertSessionHasNoErrors();
        $this->actingAs($acc)->post("/files/{$n}/advance")->assertSessionHasNoErrors();
        // 8. After-sale call and close
        $this->actingAs($sales)->post("/files/{$n}/checks", ['item' => 'after_sale_call', 'value' => 1]);
        $this->actingAs($sales)->post("/files/{$n}/advance")->assertSessionHasNoErrors();

        $f->refresh();
        $this->assertSame('closed', $f->status);
        $this->assertSame('won', $f->pricing_status);
        $this->assertGreaterThan(10, $f->history()->count());
        $this->assertGreaterThan(0, \Spatie\Activitylog\Models\Activity::where('subject_type', ShipmentFile::class)->where('subject_id', $f->id)->count());
        $this->assertNotNull(app(\App\Domain\MarginService::class)->actualProfitUsd($f));
    }
}
