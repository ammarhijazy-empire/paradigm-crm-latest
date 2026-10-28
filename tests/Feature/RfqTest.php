<?php

namespace Tests\Feature;

use App\Domain\InquiryValidator;
use App\Models\Client;
use App\Models\ShipmentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RfqTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $over = []): array
    {
        return array_replace_recursive([
            'client_id' => 1, 'direction' => 'import', 'service' => 'fcl', 'addons' => [], 'origin' => 'CNNGB', 'destination' => 'EGALY', 'incoterm' => 'FOB',
            'inq' => ['rows' => [['type' => "40'HC", 'count' => 2]], 'cargo' => 'general', 'ready' => now()->addDays(5)->toDateString(), 'free_days' => 14, 'freight_terms' => 'collect', 'pay' => 'cash_in_advance'],
        ], $over);
    }

    private function keys(array $f): array
    {
        return array_column(InquiryValidator::problems($f), 0);
    }

    #[Test]
    public function a_complete_request_has_no_problems(): void
    {
        $this->assertSame([], $this->keys($this->valid()));
    }

    #[Test]
    public function exactly_one_main_service_and_insurance_alone_is_refused(): void
    {
        $this->assertContains('rfq.p_service', $this->keys($this->valid(['service' => null])));
        $f = $this->valid(['service' => null]);
        $f['addons'] = ['insurance'];
        $this->assertContains('rfq.p_ins_alone', $this->keys($f));
    }

    #[Test]
    public function volume_rules_per_mode(): void
    {
        $lcl = $this->valid(['service' => 'lcl']);
        $lcl['inq']['rows'] = [['pieces' => 2, 'l' => 120, 'w' => 100]];
        $this->assertContains('rfq.p_row_incomplete', $this->keys($lcl));
        $lcl['inq']['rows'] = [['pieces' => 2, 'l' => 120, 'w' => 100, 'h' => 90, 'kg' => 800]];
        $this->assertSame([], $this->keys($lcl));

        $land = $this->valid(['service' => 'land', 'incoterm' => 'DAP']);
        $land['inq']['rows'] = [['truck' => 'FTL', 'count' => 1, 'kg' => 20000]];
        $this->assertSame([], $this->keys($land));
    }

    #[Test]
    public function incoterms_depend_on_mode_and_exw_needs_pickup(): void
    {
        $air = $this->valid(['service' => 'air', 'incoterm' => 'FOB']);
        $air['inq']['rows'] = [['pieces' => 1, 'l' => 50, 'w' => 50, 'h' => 50, 'kg' => 30]];
        $this->assertContains('rfq.p_incoterm', $this->keys($air));
        $this->assertContains('rfq.p_exw', $this->keys($this->valid(['incoterm' => 'EXW'])));
        $this->assertNotContains('rfq.p_exw', $this->keys($this->valid(['incoterm' => 'EXW', 'pickup_address' => 'Ningbo factory'])));
    }

    #[Test]
    public function cargo_and_payment_details(): void
    {
        $this->assertContains('rfq.p_haz', $this->keys($this->valid(['inq' => ['cargo' => 'hazardous', 'un' => '1263']])));
        $this->assertContains('rfq.p_temp', $this->keys($this->valid(['inq' => ['cargo' => 'reefer']])));
        $this->assertContains('rfq.p_credit', $this->keys($this->valid(['inq' => ['pay' => 'credit']])));
        $this->assertContains('rfq.p_ready_past', $this->keys($this->valid(['inq' => ['ready' => now()->subDays(3)->toDateString()]])));
        $this->assertContains('rfq.p_free', $this->keys($this->valid(['inq' => ['free_days' => null]])));
        $this->assertContains('rfq.p_delivery', $this->keys($this->valid(['addons' => ['trucking']])));
    }

    #[Test]
    public function incomplete_request_cannot_go_to_pricing_but_can_be_saved_as_draft(): void
    {
        $sales = $this->user('sales');
        $client = Client::factory()->create(['owner_id' => $sales->id]);
        $data = $this->valid(['client_id' => $client->id, 'destination' => null]) + ['action' => 'send'];
        $this->actingAs($sales)->post('/rfq', $data)->assertSessionHasErrors('workflow');
        $this->assertDatabaseCount('files', 0);
        $data['action'] = 'draft';
        $this->actingAs($sales)->post('/rfq', $data)->assertRedirect();
        $this->assertSame('draft', ShipmentFile::first()->pricing_status);
    }

    #[Test]
    public function sending_a_complete_request_creates_file_numbers_and_moves_it_to_pricing(): void
    {
        $sales = $this->user('sales');
        $sales->forceFill(['initials' => 'AH'])->save();
        $client = Client::factory()->create(['owner_id' => $sales->id]);
        $this->actingAs($sales)->post('/rfq', $this->valid(['client_id' => $client->id]) + ['action' => 'send'])->assertRedirect();
        $this->actingAs($sales)->post('/rfq', $this->valid(['client_id' => $client->id]) + ['action' => 'send']);
        [$a, $b] = ShipmentFile::orderBy('id')->get()->all();
        $ym = now('Africa/Cairo')->format('ym');
        $this->assertSame("AH-{$ym}-1", $a->ref);
        $this->assertSame("AH-{$ym}-2", $b->ref);
        $this->assertMatchesRegularExpression('/^PC-\d{2}-\d{3}$/', $a->number);
        $this->assertNotSame($a->number, $b->number);
        $this->assertSame(2, $a->stage);
        $this->assertSame('pending', $a->pricing_status);
    }

    #[Test]
    public function sales_cannot_create_a_request_for_another_salespersons_client(): void
    {
        $sales = $this->user('sales');
        $other = Client::factory()->create();
        $this->actingAs($sales)->post('/rfq', $this->valid(['client_id' => $other->id]) + ['action' => 'send'])->assertNotFound();
    }

    #[Test]
    public function the_live_check_endpoint_returns_translated_problems(): void
    {
        $sales = $this->user('sales');
        $client = Client::factory()->create(['owner_id' => $sales->id]);
        $r = $this->actingAs($sales)->postJson('/rfq/check', $this->valid(['client_id' => $client->id, 'origin' => null]) + ['action' => 'send'])->assertOk();
        $this->assertContains(__('rfq.p_origin'), $r->json('problems'));
    }
}
