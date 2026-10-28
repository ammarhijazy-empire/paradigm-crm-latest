<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CostLine;
use App\Models\Invoice;
use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Runs legacy:import against a small copy of the old Supabase tables (same column names), twice. */
class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    private function legacy(): void
    {
        $path = storage_path('framework/testing-legacy.sqlite');
        @unlink($path);
        touch($path);
        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => false]]);
        DB::purge('legacy');
        $s = Schema::connection('legacy');
        $t = fn (string $name, array $cols) => $s->create($name, function (Blueprint $b) use ($cols) {
            foreach ($cols as $c) {
                $b->text($c)->nullable();
            }
        });
        $t('profiles', ['id', 'full_name', 'email', 'phone', 'roles', 'sales_target', 'commission_pct', 'active', 'created_at', 'initials', 'gets_leads']);
        $t('vendors', ['id', 'name', 'type', 'city', 'contact', 'phone', 'email', 'notes', 'created_at', 'country_code']);
        $t('clients', ['id', 'company', 'contact', 'phone', 'email', 'sector', 'products', 'current_forwarder', 'source', 'notes', 'owner_id', 'status', 'next_follow_up', 'next_order_date', 'credit_limit', 'credit_days', 'created_at', 'tax_id']);
        $t('files', ['id', 'no', 'client_id', 'owner_id', 'ops_owner_id', 'stage', 'status', 'inq', 'checks', 'ship', 'eta', 'bl', 'acid_no', 'transit', 'stage_since', 'closed_at', 'lost_stage', 'lost_cat', 'lost_reason', 'created_at', 'updated_at', 'rfq_in_ref', 'pricing_status', 'quote']);
        $t('file_booking', ['file_id', 'other_party', 'client_party', 'acid', 'details', 'updated_at']);
        $t('vendor_quotes', ['id', 'file_id', 'ref', 'vendor_id', 'vendor_ref', 'currency', 'amount', 'valid_until', 'transit', 'note', 'chosen', 'created_by', 'created_at', 'lane_origin', 'lane_city', 'lane_dest', 'lane_incoterm', 'lane_equip']);
        $t('cost_line_actual', ['cost_line_id', 'file_id', 'act', 'paid_at', 'updated_at']);
        $t('cost_lines', ['id', 'file_id', 'sort', 'type', 'vendor_id', 'currency', 'fx', 'qty', 'unit', 'rate', 'est', 'act', 'due', 'paid_at']);
        $t('quote_lines', ['id', 'file_id', 'sort', 'section', 'item', 'basis', 'qty', 'unit_price', 'currency', 'active']);
        $t('quote_rounds', ['id', 'file_id', 'our_price', 'client_counter', 'note', 'outcome', 'by_id', 'created_at']);
        $t('file_pay_terms', ['file_id', 'method', 'credit_days', 'decision', 'decided_by', 'decided_at', 'decision_note', 'updated_at']);
        $t('cost_reviews', ['id', 'file_id', 'status', 'note', 'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_note']);
        $t('reprice_requests', ['id', 'file_id', 'target', 'note', 'requested_by', 'requested_at', 'answer', 'answered_by', 'answered_at']);
        $t('invoice_lines', ['id', 'invoice_id', 'file_id', 'sort', 'item', 'basis', 'qty', 'unit_price', 'taxable', 'active']);
        $t('invoices', ['id', 'file_id', 'currency', 'no', 'status', 'eta_no', 'issued_at', 'issued_by', 'created_at']);
        $t('payments', ['id', 'file_id', 'date', 'amount', 'currency', 'method', 'ref', 'created_by', 'created_at']);
        $t('activities', ['id', 'client_id', 'type', 'note', 'date', 'by_id', 'created_at', 'rating', 'file_id']);
        $t('file_history', ['id', 'file_id', 'stage', 'note', 'by_id', 'at']);
        $t('file_notes', ['id', 'file_id', 'body', 'mentions', 'by_id', 'created_at']);
        $t('file_updates', ['id', 'file_id', 'type', 'text', 'by_id', 'created_at']);
        $t('file_events', ['id', 'file_id', 'kind', 'val', 'by_id', 'at']);
        $t('documents', ['id', 'file_id', 'kind', 'name', 'path', 'size', 'uploaded_by', 'created_at']);
        $t('exchange_rates', ['currency', 'per_usd', 'locked', 'source']);
        $t('settings', ['id', 'min_margin']);
        $t('ref_counters', ['key', 'n']);

        $db = DB::connection('legacy');
        $db->table('profiles')->insert([
            ['id' => 'p1', 'full_name' => 'Abdullah', 'email' => 'abdullah@paradigmcargo.com', 'roles' => '{sales}', 'sales_target' => '6000', 'commission_pct' => '10', 'active' => '1', 'initials' => 'AH', 'gets_leads' => '1', 'created_at' => '2026-10-01'],
            ['id' => 'p2', 'full_name' => 'Hassan', 'email' => 'hassan@paradigmcargo.com', 'roles' => '{ops,customs}', 'sales_target' => '0', 'commission_pct' => '0', 'active' => '1', 'initials' => 'HS', 'gets_leads' => '0', 'created_at' => '2026-10-01'],
        ]);
        $db->table('vendors')->insert(['id' => 'v1', 'name' => 'Sino Bridge', 'type' => 'وكيل شحن', 'email' => 'Rates@Sino.example', 'country_code' => 'CN', 'created_at' => '2026-10-01']);
        $db->table('clients')->insert([
            ['id' => 'c1', 'company' => 'Nile Polymers', 'contact' => 'Sherif', 'phone' => '+20 100 234 5678', 'email' => 'sherif@nile.example', 'sector' => 'تجارة', 'owner_id' => 'p1', 'status' => 'repeat', 'credit_limit' => '0', 'credit_days' => '0', 'created_at' => '2026-10-02', 'notes' => 'Prefers WhatsApp'],
            ['id' => 'c2', 'company' => 'Nile Polymers (dup)', 'contact' => 'Sherif', 'phone' => '01002345678', 'email' => 'other@nile.example', 'sector' => 'زراعة', 'owner_id' => 'p1', 'status' => 'lead', 'credit_limit' => '0', 'credit_days' => '0', 'created_at' => '2026-10-03', 'notes' => null],
        ]);
        $db->table('files')->insert(['id' => 'f1', 'no' => 'PC-26-009', 'client_id' => 'c1', 'owner_id' => 'p1', 'ops_owner_id' => 'p2', 'stage' => '7', 'status' => 'done',
            'inq' => json_encode(['vol' => ['FCL' => ['type' => "40'HC", 'count' => '1']], 'product' => 'Dental chairs', 'services' => ['FCL', 'CC'], 'cargoType' => 'general', 'incoterm' => 'FOB', 'direction' => 'import', 'originPoint' => 'Guangzhou (Nansha) (CNCAN)', 'destPoint' => 'Ain Sokhna (EGSOK)', 'readyDate' => '2026-10-27', 'freeTime' => '14 days DEM/DET', 'freightTerm' => 'collect']),
            'checks' => json_encode(['contact' => true, 'quoteSent' => true, 'clientOk' => true, 'delivered' => true, 'release' => true, 'collected' => true, 'call' => true]),
            'ship' => json_encode(['carrier' => 'MSC', 'etd' => '2026-10-20', 'blDate' => '2026-10-20', 'vessel' => 'MSC AURORA / 042E', 'container' => 'MSCU1234567']),
            'quote' => json_encode(['tt' => 30, 'etd' => '2026-10-31', 'carrier' => 'MSC']), 'eta' => '2026-11-20', 'bl' => 'MEDU1', 'acid_no' => '4551026100009',
            'stage_since' => '2026-11-21', 'closed_at' => '2026-11-25', 'created_at' => '2026-10-05', 'updated_at' => '2026-11-25', 'rfq_in_ref' => 'AH-2610-3', 'pricing_status' => 'sent']);
        $db->table('vendor_quotes')->insert(['id' => 'q1', 'file_id' => 'f1', 'ref' => 'AH-2610-3-Q1', 'vendor_id' => 'v1', 'currency' => 'USD', 'amount' => '1900', 'valid_until' => '2026-11-05', 'transit' => '30 days', 'chosen' => '1', 'created_by' => 'p1', 'created_at' => '2026-10-06']);
        $db->table('cost_lines')->insert(['id' => 'cl1', 'file_id' => 'f1', 'sort' => '1', 'type' => 'freight', 'vendor_id' => 'v1', 'currency' => 'USD', 'fx' => '1', 'qty' => '1', 'rate' => '1900', 'est' => '1900', 'due' => '2026-11-30']);
        $db->table('cost_line_actual')->insert(['cost_line_id' => 'cl1', 'file_id' => 'f1', 'act' => '1950', 'paid_at' => '2026-11-28', 'updated_at' => '2026-11-28']);
        $db->table('quote_lines')->insert(['id' => 'ql1', 'file_id' => 'f1', 'sort' => '1', 'section' => 'freight', 'item' => 'Ocean freight', 'qty' => '1', 'unit_price' => '2300', 'currency' => 'USD', 'active' => '1']);
        $db->table('quote_rounds')->insert(['id' => 'r1', 'file_id' => 'f1', 'our_price' => '2300', 'outcome' => 'accepted', 'by_id' => 'p1', 'created_at' => '2026-10-07']);
        $db->table('file_pay_terms')->insert(['file_id' => 'f1', 'method' => 'credit_facility', 'credit_days' => '30', 'decision' => 'approved', 'decided_by' => 'p2', 'updated_at' => '2026-10-07']);
        $db->table('invoices')->insert(['id' => 'i1', 'file_id' => 'f1', 'currency' => 'USD', 'no' => 'INV-26-0001', 'status' => 'issued', 'issued_at' => '2026-11-22', 'issued_by' => 'p2', 'created_at' => '2026-11-22']);
        $db->table('invoice_lines')->insert(['id' => 'il1', 'invoice_id' => 'i1', 'file_id' => 'f1', 'sort' => '1', 'item' => 'Ocean freight', 'qty' => '1', 'unit_price' => '2300', 'taxable' => '1', 'active' => '1']);
        $db->table('payments')->insert(['id' => 'pay1', 'file_id' => 'f1', 'date' => '2026-11-26', 'amount' => '2622', 'currency' => 'USD', 'method' => 'bank', 'created_by' => 'p2', 'created_at' => '2026-11-26']);
        $db->table('activities')->insert(['id' => 'a1', 'client_id' => 'c1', 'type' => 'fail', 'note' => 'no answer', 'by_id' => 'p1', 'created_at' => '2026-10-04']);
        $db->table('file_history')->insert(['id' => '1', 'file_id' => 'f1', 'stage' => '7', 'note' => 'closed', 'by_id' => 'p1', 'at' => '2026-11-25']);
        $db->table('exchange_rates')->insert(['currency' => 'EGP', 'per_usd' => '48.7', 'locked' => '0', 'source' => 'x']);
        $db->table('settings')->insert(['id' => '1', 'min_margin' => '250']);
        $db->table('ref_counters')->insert(['key' => 'AH-2610', 'n' => '3']);
    }

    #[Test]
    public function the_import_maps_old_data_and_running_it_twice_changes_nothing(): void
    {
        $this->legacy();
        User::factory()->role('admin')->create(['email' => 'ammar@paradigmcargo.com']);
        $this->assertSame(0, Artisan::call('legacy:import'), Artisan::output());
        $counts = fn () => [User::count(), Client::withoutGlobalScopes()->count(), ShipmentFile::withoutGlobalScopes()->count(), CostLine::count(), Invoice::count(), \App\Models\Payment::count(), \App\Models\Activity::count()];
        $first = $counts();
        $this->assertSame(0, Artisan::call('legacy:import'), Artisan::output());
        $this->assertSame($first, $counts(), 'second run duplicated rows');

        $this->assertSame([3, 1, 1, 1, 1, 1], array_slice($first, 0, 6)); // 3 users (2 + admin); duplicate client merged by phone
        $u = User::where('email', 'hassan@paradigmcargo.com')->first();
        $this->assertEqualsCanonicalizing(['ops', 'customs'], $u->getRoleNames()->all());

        $f = ShipmentFile::withoutGlobalScopes()->first();
        $this->assertSame('PC-26-009', $f->number);
        $this->assertSame('AH-2610-3', $f->ref);
        $this->assertSame('closed', $f->status);
        $this->assertSame('fcl', $f->service);
        $this->assertSame(['clearance'], $f->addons);
        $this->assertSame('CNCAN', $f->origin);
        $this->assertSame('EGSOK', $f->destination);
        $this->assertSame(14, $f->inq['free_days']);
        $this->assertTrue($f->check('client_approval'));
        $this->assertTrue($f->check('after_sale_call'));
        $this->assertTrue($f->check('acid_nafeza'));
        $this->assertSame(30, $f->quote_transit_days);
        $this->assertSame('MSC AURORA / 042E', $f->booking->vessel_voyage);

        $this->assertEquals(1950, (float) CostLine::first()->actual);
        $inv = Invoice::first();
        $this->assertEquals(2622, (float) $inv->total);
        $this->assertSame('paid', $inv->status);
        $this->assertSame('approved', $f->payTerms->status);
        $this->assertSame('credit', $f->payTerms->method);
        $c = Client::withoutGlobalScopes()->first();
        $this->assertSame('other', $c->sector);
        $this->assertSame('تجارة', $c->sector_other);
        $this->assertEquals(250, \App\Support\Settings::get('min_margin_usd'));
        // New file numbers continue after the imported ones
        $this->assertSame('PC-26-010', \App\Domain\RefGenerator::fileNumber());
    }

    #[Test]
    public function dry_run_saves_nothing(): void
    {
        $this->legacy();
        User::factory()->role('admin')->create();
        Artisan::call('legacy:import', ['--dry-run' => true]);
        $this->assertSame(0, ShipmentFile::withoutGlobalScopes()->count());
    }
}
