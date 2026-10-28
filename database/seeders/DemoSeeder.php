<?php

namespace Database\Seeders;

use App\Domain\ClientService;
use App\Domain\FileStageService;
use App\Domain\InvoiceService;
use App\Domain\LeadService;
use App\Domain\PricingService;
use App\Domain\QuotationService;
use App\Domain\RfqService;
use App\Domain\ShippingService;
use App\Models\Activity;
use App\Models\DocCheck;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * Sample data for the TEST site only. Every file is moved through the real
 * services, so the data obeys the same rules as live data.
 * Run: php artisan db:seed --class=DemoSeeder   (refuses to run in production)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder does not run in production.');
            return;
        }
        $this->call(DatabaseSeeder::class);
        Queue::fake();
        Mail::fake();
        Event::fake([\App\Events\CrmEvent::class]);

        $pw = Hash::make(env('DEMO_PASSWORD', 'Paradigm2026'));
        $mk = function (string $name, string $email, string $ini, array $roles, array $extra = []) use ($pw) {
            $u = User::firstOrNew(['email' => $email]);
            $u->forceFill(['name' => $name, 'password' => $pw, 'initials' => $ini, 'locale' => 'ar', 'active' => true] + $extra)->save();
            $u->syncRoles($roles);
            return $u;
        };
        $admin = $mk('Ammar', 'ammar@paradigmcargo.com', 'AM', ['admin', 'sales'], ['sales_target' => 9000, 'gets_leads' => true]);
        $ah = $mk('Abdullah', 'abdullah@paradigmcargo.com', 'AH', ['sales'], ['sales_target' => 6000, 'commission_pct' => 10, 'gets_leads' => true]);
        $mo = $mk('Mohy', 'mohy@paradigmcargo.com', 'MO', ['sales'], ['sales_target' => 6000, 'commission_pct' => 10, 'gets_leads' => true]);
        $pricing = $mk('Nour', 'nour@paradigmcargo.com', 'NR', ['pricing']);
        $review = $mk('Karim', 'karim@paradigmcargo.com', 'KR', ['pricing_review']);
        $ops = $mk('Hassan', 'hassan@paradigmcargo.com', 'HS', ['ops']);
        $customs = $mk('Tamer', 'tamer@paradigmcargo.com', 'TM', ['customs']);
        $acc = $mk('Mona', 'mona@paradigmcargo.com', 'MN', ['accounts']);
        $mk('Youssef', 'youssef@paradigmcargo.com', 'YS', []);

        $v = collect([
            ['Sino Bridge Logistics', 'agent', 'CN', 'rates@sinobridge.example'], ['Eastwind Shipping Agency', 'agent', 'CN', 'sales@eastwind.example'],
            ['MSC Egypt', 'line', 'CH', 'eg.pricing@msc.example'], ['CMA CGM Alexandria', 'line', 'FR', 'alx.quotes@cma.example'], ['Bosphorus Freight', 'agent', 'TR', 'ops@bosphorus.example'],
            ['SkyLink Cargo', 'air', 'AE', 'air@skylink.example'], ['Al Mostakbal Clearance', 'broker', 'EG', 'info@mostakbal.example'], ['Delta Trucking', 'truck', 'EG', 'dispatch@deltatruck.example'],
        ])->map(fn ($x) => Vendor::updateOrCreate(['name' => $x[0]], ['type' => $x[1], 'country' => $x[2], 'email' => $x[3], 'active' => true]));

        $cs = app(ClientService::class);
        $clients = collect([
            [$ah, 'Nile Polymers Co.', 'Eng. Sherif Adel', '+20 100 234 5678', 'sherif@nilepolymers.example', 'plastics', 'repeat', 'PP and PE granules'],
            [$mo, 'Delta Home Furniture', 'Walid Samy', '+20 122 876 1100', 'walid@deltahome.example', 'furniture', 'active', 'MDF boards'],
            [$ah, 'Cairo MedTech', 'Dr. Rania Fouad', '+20 111 555 2040', 'rania@cairomedtech.example', 'medical', 'active', 'Surgical consumables'],
            [$admin, 'Sinai Steel Trading', 'Hany Morsy', '+20 106 330 9922', 'hany@sinaisteel.example', 'steel', 'repeat', 'Steel coils'],
            [$mo, 'Green Valley Foods', 'Salma Tarek', '+20 127 404 6060', 'salma@greenvalley.example', 'food', 'contacted', 'Frozen vegetables'],
            [$ah, 'Horizon Textiles', 'Mostafa Nabil', '+20 101 777 4521', 'mostafa@horizontex.example', 'textiles', 'lead', 'Cotton yarn'],
        ])->map(function ($c) use ($cs) {
            $existing = \App\Models\Client::withoutGlobalScopes()->where('email', $c[4])->first();
            return $existing ?? $cs->create(['company' => $c[1], 'contact_person' => $c[2], 'phone' => $c[3], 'email' => $c[4], 'sector' => $c[5], 'status' => $c[6], 'products' => $c[7], 'next_follow_up' => today()->toDateString()], $c[0]);
        });
        if (\App\Models\ShipmentFile::withoutGlobalScopes()->exists()) {
            $this->command?->info('Demo files already exist — skipped.');
            return;
        }

        Activity::create(['client_id' => $clients[0]->id, 'user_id' => $ah->id, 'type' => 'call', 'body' => 'Next PE order expected mid next month.', 'rating' => 4, 'happened_at' => now()->subDays(2)]);
        $rfq = app(RfqService::class);
        $stages = app(FileStageService::class);
        $pr = app(PricingService::class);
        $qs = app(QuotationService::class);
        $sh = app(ShippingService::class);
        $inv = app(InvoiceService::class);
        $base = fn ($client, array $over = []) => $this->merge([
            'client_id' => $client->id, 'direction' => 'import', 'service' => 'fcl', 'addons' => ['clearance', 'trucking'], 'origin' => 'CNNGB', 'destination' => 'EGALY',
            'incoterm' => 'FOB', 'delivery_address' => '10th of Ramadan, Industrial Zone A3',
            'inq' => ['rows' => [['type' => "40'HC", 'count' => 2]], 'cargo' => 'general', 'commodity' => 'PP granules in 25 kg bags', 'ready' => today()->addDays(12)->toDateString(),
                'price_by' => today()->addDay()->toDateString(), 'free_days' => 14, 'freight_terms' => 'collect', 'pay' => 'cash_in_advance'],
        ], $over);

        // 1) Draft at stage 1
        $rfq->create($base($clients[5], ['service' => 'lcl', 'origin' => 'INNSA', 'inq' => ['rows' => [['pieces' => 12, 'l' => 120, 'w' => 100, 'h' => 110, 'kg' => 4200]], 'free_days' => null]]), $ah, false);

        // 2) Waiting for pricing, with two vendor quotes
        $f2 = $rfq->create($base($clients[0]), $ah, true);
        $pr->sendRfq($f2, [$v[0]->id, $v[2]->id, $v[3]->id], $pricing);
        $pr->recordQuote($f2, ['vendor_id' => $v[0]->id, 'carrier' => 'COSCO', 'currency' => 'USD', 'freight' => 2380, 'surcharges' => [['name' => 'BAF', 'amount' => 400]], 'transit_days' => 28, 'free_days' => 14, 'valid_until' => today()->addDays(20)->toDateString()], $pricing);
        $pr->recordQuote($f2, ['vendor_id' => $v[3]->id, 'carrier' => 'CMA CGM', 'currency' => 'USD', 'freight' => 2600, 'surcharges' => [['name' => 'BAF', 'amount' => 360]], 'transit_days' => 24, 'free_days' => 10, 'valid_until' => today()->addDays(14)->toDateString()], $pricing);
        $pr->recordQuote($f2, ['vendor_id' => $v[2]->id, 'carrier' => 'MSC', 'currency' => 'USD', 'freight' => 2450, 'transit_days' => 29, 'free_days' => 14, 'valid_until' => today()->subDays(2)->toDateString(), 'conditions' => 'Draft read from vendor email'], $pricing, 'ai', null, 'draft');

        // 3) Priced, quoted with a low margin → needs management approval
        $f3 = $rfq->create($base($clients[1], ['origin' => 'TRAMR', 'incoterm' => 'EXW', 'pickup_address' => 'Esenyurt, Istanbul', 'addons' => ['clearance'], 'delivery_address' => null, 'inq' => ['rows' => [['type' => "40'GP", 'count' => 1]], 'commodity' => 'MDF boards', 'pay' => 'credit', 'credit_days' => 30]]), $mo, true);
        $this->priceAndApprove($f3, $v, $pricing, $review, $acc, 1650, 320);
        $qs->saveLines($f3->refresh(), [['section' => 'freight', 'description_en' => 'Ocean freight Ambarli → Alexandria, 1×40\'GP', 'description_ar' => 'نولون بحري أمبارلي ← الإسكندرية، حاوية ٤٠', 'currency' => 'USD', 'amount' => 2050]], $mo);

        // 4) Booking at origin (stage 3)
        $f4 = $rfq->create($base($clients[3], ['origin' => 'CNSHA', 'destination' => 'EGSOK', 'incoterm' => 'CFR', 'inq' => ['rows' => [['type' => "20'GP", 'count' => 6]], 'commodity' => 'Cold-rolled steel coils', 'free_days' => 21]]), $admin, true);
        $this->toBooking($f4, $v, $admin, $pricing, $review, $qs, $stages, 1350, 2400, $ops);

        // 5) Shipping (stage 4) with a document blocker
        $f5 = $rfq->create($base($clients[2], ['service' => 'air', 'origin' => 'PVG', 'destination' => 'CAI', 'incoterm' => 'FCA', 'addons' => ['clearance'], 'delivery_address' => null, 'inq' => ['rows' => [['pieces' => 18, 'l' => 60, 'w' => 40, 'h' => 50, 'kg' => 410]], 'commodity' => 'Sterile surgical gloves', 'free_days' => null]]), $ah, true);
        $this->toBooking($f5, $v, $ah, $pricing, $review, $qs, $stages, 1720, 2160, $ops, 'air');
        $sh->saveBooking($f5->refresh(), ['carrier' => 'Emirates SkyCargo', 'bl_no' => '176-55210934', 'etd' => today()->subDay()->toDateString(), 'atd' => today()->subDay()->toDateString(), 'eta' => today()->addDay()->toDateString(), 'booking_no' => 'EK-88421', 'vessel_voyage' => 'EK 307 / EK 927'], $ops);
        $stages->tick($f5, 'booking_done', true, $ops);
        $stages->advance($f5, $ops);
        $stages->tick($f5, 'bl_checked', true, $ops);
        DocCheck::create(['file_id' => $f5->id, 'severity' => 'blocker', 'field' => 'gross_weight', 'message' => json_encode(['en' => 'Gross weight on packing list is 386 kg; AWB says 410 kg.', 'ar' => 'الوزن القائم في قائمة التعبئة ٣٨٦ كجم، والبوليصة مكتوب فيها ٤١٠ كجم.'], JSON_UNESCAPED_UNICODE), 'status' => 'open']);
        DocCheck::create(['file_id' => $f5->id, 'severity' => 'warning', 'field' => 'hs_code', 'message' => json_encode(['en' => 'HS 4015.12 on the invoice; certificate of origin shows 4015.19.', 'ar' => 'البند الجمركي في الفاتورة 4015.12 وفي شهادة المنشأ 4015.19.'], JSON_UNESCAPED_UNICODE), 'status' => 'open']);

        // 6) Invoicing (stage 6)
        $f6 = $rfq->create($base($clients[3], ['origin' => 'CNSHA', 'destination' => 'EGSOK', 'incoterm' => 'CFR', 'inq' => ['rows' => [['type' => "20'GP", 'count' => 4]], 'commodity' => 'Steel coils', 'pay' => 'credit', 'credit_days' => 45, 'free_days' => 21]]), $admin, true);
        $this->toBooking($f6, $v, $admin, $pricing, $review, $qs, $stages, 900, 1400, $ops, 'fcl', $acc);
        $sh->saveBooking($f6->refresh(), ['carrier' => 'MSC', 'bl_no' => 'MEDUSH448190', 'etd' => today()->subDays(30)->toDateString(), 'atd' => today()->subDays(30)->toDateString(), 'eta' => today()->subDays(3)->toDateString()], $ops);
        $stages->tick($f6, 'booking_done', true, $ops);
        $stages->advance($f6, $ops);
        $stages->advance($f6, $ops);
        foreach (['arrival_notice', 'do_collected', 'customs_cleared', 'delivered'] as $c) {
            $stages->tick($f6, $c, true, $customs);
        }
        $stages->tick($f6, 'release_approved', true, $acc);
        $stages->advance($f6, $customs);
        $created = $inv->createFromFile($f6->refresh(), $acc, []);
        $inv->recordPayment($created[0], $acc, round((float) $created[0]->total / 2, 2), today()->toDateString(), 'bank', 'TRX-1001');

        // Leads
        $leads = app(LeadService::class);
        $leads->receive(['company' => 'Atlas Ceramics', 'contact_person' => 'Fady Ramzy', 'phone' => '+20 128 300 4411', 'email' => 'fady@atlas.example', 'message' => 'Monthly FCL from Foshan to Alexandria']);
        $leads->receive(['company' => 'Fresh Farm Exports', 'contact_person' => 'Nada Helmy', 'phone' => '+20 100 900 1212', 'email' => 'nada@freshfarm.example', 'message' => 'Reefer export of oranges to Rotterdam']);

        $this->command?->info('Demo data ready. Password for all demo users: '.env('DEMO_PASSWORD', 'Paradigm2026'));
    }

    /** Deep merge, but lists (addons, rows) are replaced, not merged by index. */
    private function merge(array $base, array $over): array
    {
        $r = array_replace_recursive($base, $over);
        if (array_key_exists('addons', $over)) {
            $r['addons'] = $over['addons'];
        }
        if (isset($over['inq']['rows'])) {
            $r['inq']['rows'] = $over['inq']['rows'];
        }
        return $r;
    }

    private function priceAndApprove($f, $v, User $pricing, User $review, User $acc, float $freight, float $dest): void
    {
        $pr = app(PricingService::class);
        $q = $pr->recordQuote($f, ['vendor_id' => $v[4]->id, 'carrier' => 'Arkas', 'currency' => 'USD', 'freight' => $freight, 'destination_charges' => $dest, 'transit_days' => 6, 'free_days' => 14, 'valid_until' => today()->addDays(15)->toDateString(), 'etd' => today()->addDays(7)->toDateString()], $pricing);
        $pr->choose($f->refresh(), $q, $pricing);
        $pr->saveQuoteMeta($f, ['quote_etd' => today()->addDays(7)->toDateString(), 'quote_transit_days' => 6, 'quote_carrier' => 'Arkas'], $pricing);
        $pr->submitForReview($f->refresh(), $pricing);
        $pr->review($f->refresh(), $review, true, null);
        if ($f->payTerms?->status === 'pending') {
            $pr->decidePayTerms($f->refresh(), $acc, true, 'OK — regular client');
        }
    }

    private function toBooking($f, $v, User $sales, User $pricing, User $review, $qs, $stages, float $cost, float $sell, User $ops, string $mode = 'fcl', ?User $acc = null): void
    {
        $this->priceAndApprove($f, $v, $pricing, $review, $acc ?? User::role('accounts')->first(), $cost, 0);
        $qs->saveLines($f->refresh(), [['section' => 'freight', 'description_en' => 'Freight', 'description_ar' => 'نولون', 'currency' => 'USD', 'amount' => $sell]], $sales);
        $qs->markSent($f->refresh(), $sales, 'ar');
        $stages->tick($f, 'client_approval', true, $sales);
        $stages->advance($f, $sales);
        $stages->tick($f, 'sales_handover', true, $sales);
        $stages->tick($f, 'acid_nafeza', true, $ops);
        $f->update(['acid_no' => '45510261'.random_int(10000000, 99999999)]);
    }
}
