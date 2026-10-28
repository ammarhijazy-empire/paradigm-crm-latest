<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Client;
use App\Models\CostLine;
use App\Models\CostReview;
use App\Models\Document;
use App\Models\ExchangeRate;
use App\Models\FileBooking;
use App\Models\FileEvent;
use App\Models\FileHistory;
use App\Models\FileNote;
use App\Models\FilePayTerm;
use App\Models\FileUpdate;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\LegacyId;
use App\Models\Payment;
use App\Models\QuoteLine;
use App\Models\QuoteRound;
use App\Models\RefCounter;
use App\Models\RepriceRequest;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorQuote;
use App\Support\Phone;
use App\Support\Sectors;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Copies the old Supabase CRM into this database.
 * Repeatable: old → new ids are kept in legacy_ids, so a second run updates rows instead of duplicating them.
 * Passwords are not copied: everyone sets a new password with "Forgot password".
 */
class LegacyImport extends Command
{
    protected $signature = 'legacy:import {--dry-run : Read and map everything, then roll back} {--only= : Comma list of steps}';

    protected $description = 'Import data from the old Supabase CRM (read-only source)';

    private ConnectionInterface $old;

    private array $report = [];

    private array $failed = [];

    /** Things worth reading that are not errors (e.g. duplicate clients merged). */
    private array $notes = [];

    private ?User $system = null;

    public const STEPS = ['users', 'vendors', 'clients', 'files', 'pricing', 'money', 'activity', 'documents', 'rates', 'counters'];

    public const CHECK_MAP = [
        'contact' => 'first_contact', 'costReq' => 'rates_requested', 'quoteSent' => 'quotation_sent', 'clientOk' => 'client_approval',
        'supplier' => 'supplier_ready', 'booking' => 'booking_done', 'preDocs' => 'docs_to_broker', 'proforma' => 'proforma_ready',
        'arrival' => 'arrival_notice', 'do' => 'do_collected', 'customs' => 'customs_cleared', 'release' => 'release_approved',
        'delivered' => 'delivered', 'costsHanded' => 'actual_costs_handed', 'invoice' => 'final_invoice', 'collected' => 'payment_collected',
        'suppliersPaid' => 'suppliers_paid', 'call' => 'after_sale_call', 'feedback' => 'feedback_logged',
    ];

    public const SERVICE_MAP = ['FCL' => 'fcl', 'LCL' => 'lcl', 'AIR' => 'air', 'LAND' => 'land', 'BULK' => 'bulk', 'RORO' => 'roro', 'SE' => 'se', 'CR' => 'courier', 'COURIER' => 'courier'];

    public const ADDON_MAP = ['CC' => 'clearance', 'TRK' => 'trucking', 'INS' => 'insurance', 'OTH' => 'other', 'OTHER' => 'other'];

    public const PAY_MAP = ['cash_in_advance' => 'cash_in_advance', 'cash_against_documents' => 'against_documents', 'cash_at_arrival' => 'on_arrival', 'credit_facility' => 'credit'];

    public function handle(): int
    {
        $this->old = DB::connection('legacy');
        try {
            $this->old->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot reach the old database: '.$e->getMessage());
            return self::FAILURE;
        }
        $steps = $this->option('only') ? explode(',', $this->option('only')) : self::STEPS;
        $this->system = User::role('admin')->orderBy('id')->first() ?? throw new \RuntimeException('Create the first management account before importing.');

        DB::beginTransaction();
        try {
            foreach (self::STEPS as $s) {
                if (in_array($s, $steps, true)) {
                    $this->info("→ {$s}");
                    $this->{'import'.ucfirst($s)}();
                }
            }
            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('Dry run: nothing was saved.');
            } else {
                DB::commit();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Stopped, nothing saved: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Table', 'Old rows', 'Imported / updated', 'Failed'], collect($this->report)->map(fn ($r, $t) => [$t, $r['old'], $r['ok'], $r['bad']])->values());
        foreach ($this->notes as $n) {
            $this->line("  • {$n}");
        }
        foreach ($this->failed as $f) {
            $this->line("  ✗ {$f}");
        }
        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    // ---------- helpers ----------
    private function map(string $table, ?string $oldId): ?int
    {
        return $oldId ? LegacyId::where('legacy_table', $table)->where('legacy_id', $oldId)->value('new_id') : null;
    }

    private function remember(string $table, string $oldId, Model $m): void
    {
        LegacyId::updateOrCreate(['legacy_table' => $table, 'legacy_id' => $oldId], ['model' => $m::class, 'new_id' => $m->getKey()]);
    }

    private function existing(string $table, string $oldId, string $class): ?Model
    {
        $id = $this->map($table, $oldId);
        return $id ? $class::withoutGlobalScopes()->find($id) : null;
    }

    private function count(string $t, int $old): void
    {
        $this->report[$t] ??= ['old' => 0, 'ok' => 0, 'bad' => 0];
        $this->report[$t]['old'] += $old;
    }

    private function ok(string $t): void
    {
        $this->report[$t]['ok']++;
    }

    private function bad(string $t, string $id, string $why): void
    {
        $this->report[$t]['bad']++;
        $this->failed[] = "{$t} {$id}: {$why}";
    }

    private function rows(string $table, string $order = 'created_at'): array
    {
        $q = $this->old->table($table);
        try {
            $rows = $q->orderBy($order)->get()->all();
        } catch (Throwable) {
            $rows = $this->old->table($table)->get()->all();
        }
        $this->count($table, count($rows));
        return $rows;
    }

    private static function json(mixed $v): array
    {
        if (is_array($v)) {
            return $v;
        }
        $d = json_decode((string) $v, true);
        return is_array($d) ? $d : [];
    }

    private static function pgArray(mixed $v): array
    {
        if (is_array($v)) {
            return $v;
        }
        $v = trim((string) $v, '{}');
        return $v === '' ? [] : array_map(fn ($x) => trim($x, '"'), str_getcsv($v));
    }

    private static function date(mixed $v): ?string
    {
        return $v ? Carbon::parse($v)->toDateString() : null;
    }

    private static function ts(mixed $v): ?Carbon
    {
        return $v ? Carbon::parse($v)->utc() : null;
    }

    private static function code(?string $point): ?string
    {
        return $point && preg_match('/\(([A-Z0-9-]{3,10})\)\s*$/', $point, $m) ? $m[1] : null;
    }

    private function sector(?string $s): array
    {
        $s = trim((string) $s);
        if ($s === '') {
            return ['other', null];
        }
        foreach (Sectors::KEYS as $k) {
            if (in_array($s, [__('sectors.'.$k, [], 'ar'), __('sectors.'.$k, [], 'en'), $k], true)) {
                return [$k, null];
            }
        }
        return ['other', $s];
    }

    // ---------- steps ----------
    private function importUsers(): void
    {
        foreach ($this->rows('profiles') as $p) {
            try {
                $email = mb_strtolower(trim((string) $p->email));
                $u = User::where('email', $email)->first() ?? new User(['email' => $email, 'password' => Hash::make(Str::random(40)), 'name' => $p->full_name ?: $email]);
                $u->forceFill([
                    'name' => $p->full_name ?: $u->name, 'phone' => $p->phone, 'initials' => $p->initials ? mb_strtoupper($p->initials) : $u->initials,
                    'sales_target' => (float) $p->sales_target, 'commission_pct' => (float) $p->commission_pct,
                    'active' => (bool) $p->active, 'gets_leads' => (bool) ($p->gets_leads ?? false),
                ])->save();
                $roles = array_values(array_intersect(self::pgArray($p->roles), User::ROLES));
                if ($u->id !== $this->system->id) {
                    $u->syncRoles($roles);
                }
                $this->remember('profiles', $p->id, $u);
                $this->ok('profiles');
            } catch (Throwable $e) {
                $this->bad('profiles', $p->id, $e->getMessage());
            }
        }
    }

    private function importVendors(): void
    {
        $types = ['وكيل شحن' => 'agent', 'خط ملاحي' => 'line', 'شركة طيران' => 'air', 'مخزن تجميع' => 'cfs', 'مخلص جمركي' => 'broker', 'شركة نقل' => 'truck'];
        foreach ($this->rows('vendors') as $v) {
            try {
                $m = $this->existing('vendors', $v->id, Vendor::class) ?? new Vendor;
                $m->fill(['name' => $v->name, 'type' => $types[$v->type] ?? (in_array($v->type, ['agent', 'line', 'air', 'cfs', 'broker', 'truck'], true) ? $v->type : 'agent'),
                    'country' => $v->country_code ? strtoupper(substr($v->country_code, 0, 2)) : null, 'contact_person' => $v->contact, 'email' => $v->email ? mb_strtolower($v->email) : null,
                    'phone' => $v->phone, 'notes' => trim(($v->city ? "City: {$v->city}\n" : '').($v->notes ?? '')), 'active' => true])->save();
                $this->remember('vendors', $v->id, $m);
                $this->ok('vendors');
            } catch (Throwable $e) {
                $this->bad('vendors', $v->id, $e->getMessage());
            }
        }
    }

    private function importClients(): void
    {
        $seen = [];
        foreach ($this->rows('clients') as $c) {
            try {
                $owner = $this->map('profiles', $c->owner_id) ?? $this->system->id;
                [$sector, $other] = $this->sector($c->sector);
                $m = $this->existing('clients', $c->id, Client::class);
                $norm = Phone::normalize($c->phone);
                if ($norm && isset($seen[$norm]) && (! $m || $m->is($seen[$norm]))) {
                    // Same phone as a client already imported in this run: merge into it.
                    $this->remember('clients', $c->id, $seen[$norm]);
                    $this->notes[] = "clients {$c->id}: merged into {$seen[$norm]->company} (same phone)";
                    $this->ok('clients');
                    continue;
                }
                $m ??= new Client;
                $m->fill([
                    'company' => $c->company, 'contact_person' => $c->contact ?: '—', 'phone' => $c->phone ?: '—', 'email' => $c->email ?: '',
                    'sector' => $sector, 'sector_other' => $other, 'products' => $c->products, 'current_forwarder' => $c->current_forwarder,
                    'source' => $c->source, 'status' => in_array($c->status, Client::STATUSES, true) ? $c->status : 'lead', 'owner_id' => $owner,
                    'next_follow_up' => self::date($c->next_follow_up), 'next_order_date' => self::date($c->next_order_date),
                    'credit_limit' => (float) $c->credit_limit, 'credit_days' => (int) $c->credit_days, 'tax_id' => $c->tax_id,
                ]);
                $m->created_at = self::ts($c->created_at) ?? now();
                $m->save();
                $this->remember('clients', $c->id, $m);
                if ($norm) {
                    $seen[$norm] = $m;
                }
                if ($c->notes) {
                    Activity::firstOrCreate(['client_id' => $m->id, 'type' => 'note', 'body' => $c->notes], ['user_id' => $owner, 'happened_at' => self::ts($c->created_at) ?? now()]);
                }
                $this->ok('clients');
            } catch (Throwable $e) {
                $this->bad('clients', $c->id, $e->getMessage());
            }
        }
    }

    private function inq(array $old): array
    {
        $services = self::pgArray($old['services'] ?? []);
        $main = null;
        foreach ($services as $s) {
            if (isset(self::SERVICE_MAP[$s])) {
                $main = self::SERVICE_MAP[$s];
                break;
            }
        }
        $addons = array_values(array_unique(array_filter(array_map(fn ($s) => self::ADDON_MAP[$s] ?? null, $services))));
        if (($old['clearance'] ?? '') === 'yes' && ! in_array('clearance', $addons, true)) {
            $addons[] = 'clearance';
        }
        $vol = $old['vol'][array_search($main, self::SERVICE_MAP, true) ?: ''] ?? [];
        $row = match ($main) {
            'fcl' => ['type' => $vol['type'] ?? ($old['container'] ?? null), 'count' => (int) ($vol['count'] ?? $old['containerCount'] ?? 0)],
            'se' => ['type' => str_contains((string) ($vol['type'] ?? ''), 'Open') ? 'Open Top' : 'Flat Rack', 'count' => (int) ($vol['count'] ?? 0)],
            'lcl', 'air', 'courier' => ['pieces' => (int) ($vol['pcs'] ?? 0), 'l' => (float) ($vol['l'] ?? 0), 'w' => (float) ($vol['w'] ?? 0), 'h' => (float) ($vol['h'] ?? 0), 'kg' => (float) ($vol['wt'] ?? $old['weight'] ?? 0)],
            'land' => ['truck' => $vol['truck'] ?? 'FTL', 'count' => (int) ($vol['count'] ?? 1), 'kg' => (float) ($vol['wt'] ?? 0)],
            'bulk' => ['tonnes' => (float) ($vol['t'] ?? $vol['wt'] ?? 0)],
            'roro' => ['count' => (int) ($vol['count'] ?? 0), 'l' => (float) ($vol['l'] ?? 0), 'w' => (float) ($vol['w'] ?? 0), 'h' => (float) ($vol['h'] ?? 0), 'kg' => (float) ($vol['wt'] ?? 0)],
            default => [],
        };
        $cargo = ['general' => 'general', 'dg' => 'hazardous', 'hazardous' => 'hazardous', 'reefer' => 'reefer', 'fragile' => 'fragile', 'liquid' => 'liquid', 'oog' => 'oog'][$old['cargoType'] ?? 'general'] ?? 'general';
        preg_match('/\d+/', (string) ($old['freeTime'] ?? ''), $fm);
        return [
            'service' => $main, 'addons' => $addons, 'direction' => ($old['direction'] ?? 'import') === 'export' ? 'export' : 'import',
            'origin' => self::code($old['originPoint'] ?? null), 'destination' => self::code($old['destPoint'] ?? null),
            'incoterm' => ($old['incoterm'] ?? '') ?: null, 'pickup_address' => trim(($old['pickupAddress'] ?? '').' '.($old['pickupCity'] ?? '')) ?: null,
            'delivery_address' => ($old['deliveryAddress'] ?? '') ?: null,
            'inq' => array_filter([
                'rows' => array_filter($row) ? [array_filter($row, fn ($v) => $v !== null && $v !== '')] : [],
                'cargo' => $cargo, 'commodity' => $old['product'] ?? null, 'hs' => $old['hs'] ?? null,
                'ready' => ($old['readyDate'] ?? '') ?: null, 'price_by' => ($old['neededBy'] ?? '') ?: null,
                'free_days' => isset($fm[0]) ? (int) $fm[0] : null, 'freight_terms' => in_array($old['freightTerm'] ?? '', ['prepaid', 'collect'], true) ? $old['freightTerm'] : null,
                'notes' => trim(($old['notes'] ?? '').(($old['freeTime'] ?? '') && ! is_numeric($old['freeTime']) ? "\nFree time: ".$old['freeTime'] : '')) ?: null,
                'insurance_value' => ($old['insuranceValue'] ?? '') ?: null, 'legacy_origin' => $old['originPoint'] ?? null, 'legacy_destination' => $old['destPoint'] ?? null,
            ], fn ($v) => $v !== null && $v !== '' && $v !== []),
        ];
    }

    private function importFiles(): void
    {
        $maxNo = [];
        foreach ($this->rows('files') as $f) {
            try {
                $sales = $this->map('profiles', $f->owner_id) ?? $this->system->id;
                $client = $this->map('clients', $f->client_id) ?? throw new \RuntimeException('client not imported');
                $i = $this->inq(self::json($f->inq));
                $ship = self::json($f->ship);
                $quote = self::json($f->quote);
                $oldChecks = self::json($f->checks);
                $checks = [];
                foreach (self::CHECK_MAP as $o => $n) {
                    if (! empty($oldChecks[$o])) {
                        $checks[$n] = true;
                    }
                }
                if ($f->acid_no) {
                    $checks['acid_nafeza'] = true;
                }
                $status = ['done' => 'closed', 'lost' => 'lost', 'open' => 'open'][$f->status] ?? 'open';
                $pricing = $status === 'closed' || $f->stage >= 3 ? 'won' : (['new' => 'pending', 'sent' => 'quoted', 'review' => 'review', 'priced' => 'priced'][$f->pricing_status] ?? 'pending');
                $sUser = User::find($sales);
                $m = $this->existing('files', $f->id, ShipmentFile::class) ?? new ShipmentFile;
                $m->fill([
                    'number' => $f->no, 'ref' => $f->rfq_in_ref ?: ($m->ref ?: ($sUser?->displayInitials() ?? 'XX').'-'.Carbon::parse($f->created_at)->format('ym').'-L'.substr(md5($f->id), 0, 4)),
                    'client_id' => $client, 'sales_id' => $sales, 'ops_id' => $this->map('profiles', $f->ops_owner_id),
                    'direction' => $i['direction'], 'service' => $i['service'] ?? 'fcl', 'addons' => $i['addons'], 'origin' => $i['origin'], 'destination' => $i['destination'],
                    'incoterm' => $i['incoterm'], 'pickup_address' => $i['pickup_address'], 'delivery_address' => $i['delivery_address'], 'inq' => $i['inq'],
                    'ship' => array_filter(['container' => $ship['container'] ?? null, 'bl_date' => $ship['blDate'] ?? null, 'pol' => $ship['pol'] ?? null, 'pod' => $ship['pod'] ?? null, 'ata' => $ship['ata'] ?? null, 'vgm' => $ship['vgm'] ?? null, 'si' => $ship['si'] ?? null]),
                    'bl_no' => $f->bl ?: null, 'carrier' => ($ship['carrier'] ?? '') ?: ($quote['carrier'] ?? null), 'etd' => ($ship['etd'] ?? '') ?: null,
                    'atd' => ($ship['blDate'] ?? '') ?: null, 'eta' => self::date($f->eta), 'acid_no' => $f->acid_no ?: null, 'pricing_status' => $pricing,
                    'quote_etd' => ($quote['etd'] ?? '') ?: null, 'quote_transit_days' => is_numeric($quote['tt'] ?? null) ? (int) $quote['tt'] : (preg_match('/\d+/', (string) $f->transit, $tm) ? (int) $tm[0] : null),
                    'quote_carrier' => ($quote['carrier'] ?? '') ?: null,
                ]);
                ShipmentFile::withStageWrite(function () use ($m, $f, $checks, $status) {
                    $m->forceStageAttributes([
                        'stage' => max(1, min(7, (int) $f->stage)), 'status' => $status, 'checks' => $checks,
                        'stage_entered_at' => self::ts($f->stage_since) ?? self::ts($f->updated_at), 'closed_at' => self::ts($f->closed_at),
                        'lost_stage' => $f->lost_stage, 'lost_category' => $f->lost_cat ? (in_array($f->lost_cat, \App\Domain\FileStageService::LOST_CATEGORIES, true) ? $f->lost_cat : 'other') : null,
                        'lost_reason' => $status === 'lost' ? ($f->lost_reason ?: '—') : null,
                    ]);
                    $m->created_at = self::ts($f->created_at) ?? now();
                    $m->save();
                });
                if (! empty($ship['vessel']) || ! empty($ship['cutoff'])) {
                    FileBooking::updateOrCreate(['file_id' => $m->id], ['vessel_voyage' => $ship['vessel'] ?? null, 'cutoff_at' => ($ship['cutoff'] ?? '') ?: null]);
                }
                $this->remember('files', $f->id, $m);
                if (preg_match('/^PC-(\d{2})-(\d+)$/', $f->no, $nm)) {
                    $maxNo[$nm[1]] = max($maxNo[$nm[1]] ?? 0, (int) $nm[2]);
                }
                $this->ok('files');
            } catch (Throwable $e) {
                $this->bad('files', $f->id, $e->getMessage());
            }
        }
        foreach ($maxNo as $yy => $n) {
            $c = RefCounter::firstOrNew(['key' => "file:{$yy}"]);
            $c->value = max((int) $c->value, $n);
            $c->save();
        }
        // Booking extras (other party, ACID)
        foreach ($this->rows('file_booking', 'updated_at') as $b) {
            if ($fid = $this->map('files', $b->file_id)) {
                FileBooking::updateOrCreate(['file_id' => $fid], ['notes' => trim(implode("\n", array_filter([$b->other_party ? 'Other party: '.$b->other_party : null, $b->client_party ? 'Client party: '.$b->client_party : null, $b->details])))]);
                $this->ok('file_booking');
            }
        }
    }

    private function importPricing(): void
    {
        foreach ($this->rows('vendor_quotes') as $q) {
            try {
                $fid = $this->map('files', $q->file_id) ?? throw new \RuntimeException('file not imported');
                $vid = $this->map('vendors', $q->vendor_id) ?? throw new \RuntimeException('vendor not imported');
                $m = $this->existing('vendor_quotes', $q->id, VendorQuote::class) ?? new VendorQuote;
                $m->fill(['file_id' => $fid, 'vendor_id' => $vid, 'ref' => $q->ref ?: 'LEG-'.substr($q->id, 0, 8), 'currency' => $q->currency ?: 'USD',
                    'freight' => (float) $q->amount, 'total' => (float) $q->amount, 'surcharges' => [], 'valid_until' => self::date($q->valid_until),
                    'transit_days' => preg_match('/\d+/', (string) $q->transit, $t) ? (int) $t[0] : null, 'conditions' => trim(($q->vendor_ref ? 'Vendor ref: '.$q->vendor_ref."\n" : '').($q->note ?? '')) ?: null,
                    'lane' => array_filter(['origin' => $q->lane_origin, 'city' => $q->lane_city, 'dest' => $q->lane_dest, 'incoterm' => $q->lane_incoterm, 'equip' => $q->lane_equip]),
                    'status' => 'confirmed', 'source' => 'manual', 'is_chosen' => (bool) $q->chosen, 'created_by' => $this->map('profiles', $q->created_by)])->save();
                if ($q->chosen) {
                    ShipmentFile::withoutGlobalScopes()->whereKey($fid)->update(['chosen_vendor_quote_id' => $m->id]);
                }
                $this->remember('vendor_quotes', $q->id, $m);
                $this->ok('vendor_quotes');
            } catch (Throwable $e) {
                $this->bad('vendor_quotes', $q->id, $e->getMessage());
            }
        }
        $actual = collect($this->rows('cost_line_actual', 'updated_at'))->keyBy('cost_line_id');
        $types = ['freight', 'origin', 'destination', 'customs', 'trucking', 'storage', 'insurance', 'other'];
        foreach ($this->rows('cost_lines', 'sort') as $c) {
            try {
                $fid = $this->map('files', $c->file_id) ?? throw new \RuntimeException('file not imported');
                $est = $c->est !== null ? (float) $c->est : (float) $c->qty * (float) $c->rate;
                $act = $actual[$c->id]->act ?? $c->act;
                $m = $this->existing('cost_lines', $c->id, CostLine::class) ?? new CostLine;
                $m->fill(['file_id' => $fid, 'type' => in_array($c->type, $types, true) ? $c->type : 'other', 'vendor_id' => $this->map('vendors', $c->vendor_id),
                    'currency' => $c->currency ?: 'USD', 'exchange_rate' => (float) ($c->fx ?: 1), 'estimated' => $est, 'actual' => $act !== null ? (float) $act : null,
                    'description' => $c->unit ? "{$c->qty} × {$c->unit}" : null, 'vendor_due_date' => self::date($c->due),
                    'vendor_paid_at' => self::ts($actual[$c->id]->paid_at ?? $c->paid_at)])->save();
                $this->remember('cost_lines', $c->id, $m);
                $this->ok('cost_lines');
            } catch (Throwable $e) {
                $this->bad('cost_lines', $c->id, $e->getMessage());
            }
        }
        foreach ($this->rows('quote_lines', 'sort') as $l) {
            try {
                $fid = $this->map('files', $l->file_id) ?? throw new \RuntimeException('file not imported');
                if (! $l->active) {
                    continue;
                }
                $m = $this->existing('quote_lines', $l->id, QuoteLine::class) ?? new QuoteLine;
                $amount = (float) ($l->qty ?: 1) * (float) $l->unit_price;
                $desc = trim($l->item.($l->basis ? " ({$l->basis})" : ''));
                $m->fill(['file_id' => $fid, 'section' => in_array($l->section, ['freight', 'origin', 'destination', 'clearance', 'trucking', 'insurance', 'other'], true) ? $l->section : 'other',
                    'description_en' => $desc, 'description_ar' => $desc, 'currency' => $l->currency ?: 'USD', 'amount' => $amount, 'sort' => (int) $l->sort])->save();
                $this->remember('quote_lines', $l->id, $m);
                $this->ok('quote_lines');
            } catch (Throwable $e) {
                $this->bad('quote_lines', $l->id, $e->getMessage());
            }
        }
        $byFile = [];
        foreach ($this->rows('quote_rounds') as $r) {
            try {
                $fid = $this->map('files', $r->file_id) ?? throw new \RuntimeException('file not imported');
                $n = ($byFile[$fid] = ($byFile[$fid] ?? 0) + 1);
                if ($n > 3) {
                    continue;
                }
                $m = QuoteRound::updateOrCreate(['file_id' => $fid, 'round' => $n], ['our_price_usd' => (float) $r->our_price, 'counter_offer_usd' => $r->client_counter !== null ? (float) $r->client_counter : null,
                    'outcome' => ['accepted' => 'accepted', 'countered' => 'countered', 'rejected' => 'rejected'][$r->outcome] ?? 'pending', 'notes' => $r->note, 'user_id' => $this->map('profiles', $r->by_id) ?? $this->system->id]);
                $this->remember('quote_rounds', $r->id, $m);
                $this->ok('quote_rounds');
            } catch (Throwable $e) {
                $this->bad('quote_rounds', $r->id, $e->getMessage());
            }
        }
        foreach ($this->rows('file_pay_terms', 'updated_at') as $t) {
            if (! $fid = $this->map('files', $t->file_id)) {
                $this->bad('file_pay_terms', $t->file_id, 'file not imported');
                continue;
            }
            $method = self::PAY_MAP[$t->method] ?? 'cash_in_advance';
            FilePayTerm::updateOrCreate(['file_id' => $fid], ['method' => $method, 'credit_days' => $t->credit_days,
                'status' => in_array($method, ['on_arrival', 'credit'], true) ? (['approved' => 'approved', 'rejected' => 'rejected'][$t->decision] ?? 'pending') : 'not_needed',
                'decided_by' => $this->map('profiles', $t->decided_by), 'decided_at' => self::ts($t->decided_at), 'note' => $t->decision_note]);
            $this->ok('file_pay_terms');
        }
        foreach ($this->rows('cost_reviews', 'submitted_at') as $r) {
            if (! $fid = $this->map('files', $r->file_id)) {
                continue;
            }
            $m = CostReview::firstOrNew(['file_id' => $fid, 'created_at' => self::ts($r->submitted_at)]);
            $m->fill(['requested_by' => $this->map('profiles', $r->submitted_by) ?? $this->system->id, 'reviewer_id' => $this->map('profiles', $r->reviewed_by),
                'status' => ['approved' => 'approved', 'returned' => 'returned', 'rejected' => 'returned'][$r->status] ?? 'pending', 'reason' => $r->review_note ?: $r->note, 'decided_at' => self::ts($r->reviewed_at)])->save();
            $this->ok('cost_reviews');
        }
        foreach ($this->rows('reprice_requests', 'requested_at') as $r) {
            if (! $fid = $this->map('files', $r->file_id)) {
                continue;
            }
            RepriceRequest::firstOrCreate(['file_id' => $fid, 'created_at' => self::ts($r->requested_at)], ['requested_by' => $this->map('profiles', $r->requested_by) ?? $this->system->id,
                'reason' => $r->note ?: '—', 'target_usd' => $r->target, 'status' => $r->answered_at ? 'answered' : 'open', 'answered_by' => $this->map('profiles', $r->answered_by), 'answer' => $r->answer]);
            $this->ok('reprice_requests');
        }
    }

    private function importMoney(): void
    {
        $lines = collect($this->rows('invoice_lines', 'sort'))->groupBy('invoice_id');
        foreach ($this->rows('invoices') as $i) {
            try {
                $fid = $this->map('files', $i->file_id) ?? throw new \RuntimeException('file not imported');
                $f = ShipmentFile::withoutGlobalScopes()->find($fid);
                $m = $this->existing('invoices', $i->id, Invoice::class) ?? new Invoice;
                $issued = self::ts($i->issued_at) ?? self::ts($i->created_at) ?? now();
                $days = (int) ($f->payTerms?->credit_days ?? 0);
                $m->fill(['number' => $i->no ?: 'INV-L-'.substr($i->id, 0, 6), 'file_id' => $fid, 'client_id' => $f->client_id, 'currency' => $i->currency ?: 'USD',
                    'issue_date' => $issued->toDateString(), 'due_date' => $issued->copy()->addDays($days)->toDateString(), 'e_invoice_no' => $i->eta_no,
                    'status' => 'issued', 'created_by' => $this->map('profiles', $i->issued_by) ?? $this->system->id])->save();
                $m->lines()->delete();
                $sub = 0;
                $vat = 0;
                foreach (($lines[$i->id] ?? collect())->where('active', true)->values() as $k => $l) {
                    $amt = round((float) ($l->qty ?: 1) * (float) $l->unit_price, 2);
                    $v = $l->taxable ? round($amt * 0.14, 2) : 0;
                    InvoiceLine::create(['invoice_id' => $m->id, 'description_en' => $l->item, 'description_ar' => $l->item, 'qty' => (float) ($l->qty ?: 1), 'unit_price' => (float) $l->unit_price, 'vat_applies' => (bool) $l->taxable, 'vat_amount' => $v, 'amount' => $amt, 'sort' => $k]);
                    $sub += $amt;
                    $vat += $v;
                }
                $m->update(['subtotal' => $sub, 'vat_total' => $vat, 'total' => round($sub + $vat, 2)]);
                $this->remember('invoices', $i->id, $m);
                $this->ok('invoices');
            } catch (Throwable $e) {
                $this->bad('invoices', $i->id, $e->getMessage());
            }
        }
        // Old payments belong to the file; attach each to that file's invoice in the same currency.
        foreach ($this->rows('payments', 'date') as $p) {
            try {
                $fid = $this->map('files', $p->file_id) ?? throw new \RuntimeException('file not imported');
                $inv = Invoice::where('file_id', $fid)->where('currency', $p->currency ?: 'USD')->first() ?? throw new \RuntimeException('no invoice in '.$p->currency);
                if (! $this->map('payments', $p->id)) {
                    $m = Payment::create(['invoice_id' => $inv->id, 'amount' => (float) $p->amount, 'paid_at' => self::date($p->date) ?? now()->toDateString(), 'method' => $p->method, 'reference' => $p->ref, 'recorded_by' => $this->map('profiles', $p->created_by) ?? $this->system->id]);
                    $this->remember('payments', $p->id, $m);
                }
                $paid = (float) $inv->payments()->sum('amount');
                $inv->update(['paid' => min($paid, (float) $inv->total), 'status' => $paid >= (float) $inv->total - 0.01 ? 'paid' : 'partly_paid']);
                $this->ok('payments');
            } catch (Throwable $e) {
                $this->bad('payments', $p->id, $e->getMessage());
            }
        }
    }

    private function importActivity(): void
    {
        foreach ($this->rows('activities') as $a) {
            if (! $cid = $this->map('clients', $a->client_id)) {
                $this->bad('activities', $a->id, 'client not imported');
                continue;
            }
            if ($this->map('activities', $a->id)) {
                $this->ok('activities');
                continue;
            }
            $m = Activity::create(['client_id' => $cid, 'file_id' => $this->map('files', $a->file_id), 'user_id' => $this->map('profiles', $a->by_id) ?? $this->system->id,
                'type' => in_array($a->type, ['call', 'whatsapp', 'meeting', 'email', 'note'], true) ? $a->type : ($a->type === 'fail' ? 'call' : 'note'),
                'body' => ($a->type === 'fail' ? '[No answer] ' : '').($a->note ?: '—'), 'rating' => $a->rating ?: null, 'happened_at' => self::ts($a->created_at) ?? self::ts($a->date) ?? now()]);
            $this->remember('activities', $a->id, $m);
            $this->ok('activities');
        }
        foreach ($this->rows('file_history', 'at') as $h) {
            if (! $fid = $this->map('files', $h->file_id)) {
                continue;
            }
            if (! $this->map('file_history', (string) $h->id)) {
                $m = FileHistory::create(['file_id' => $fid, 'user_id' => $this->map('profiles', $h->by_id), 'action' => 'legacy', 'note' => trim(($h->stage ? "[{$h->stage}] " : '').($h->note ?? ''))]);
                $m->created_at = self::ts($h->at);
                $m->save();
                $this->remember('file_history', (string) $h->id, $m);
            }
            $this->ok('file_history');
        }
        foreach ($this->rows('file_notes') as $n) {
            if (($fid = $this->map('files', $n->file_id)) && ! $this->map('file_notes', (string) $n->id)) {
                $m = FileNote::create(['file_id' => $fid, 'user_id' => $this->map('profiles', $n->by_id) ?? $this->system->id, 'body' => $n->body, 'mentions' => array_values(array_filter(array_map(fn ($x) => $this->map('profiles', $x), self::pgArray($n->mentions))))]);
                $this->remember('file_notes', (string) $n->id, $m);
            }
            $this->ok('file_notes');
        }
        foreach ($this->rows('file_updates') as $u) {
            if (($fid = $this->map('files', $u->file_id)) && ! $this->map('file_updates', $u->id)) {
                $m = FileUpdate::create(['file_id' => $fid, 'user_id' => $this->map('profiles', $u->by_id) ?? $this->system->id, 'kind' => 'custom', 'channel' => 'whatsapp', 'body' => trim(($u->type ? "[{$u->type}] " : '').$u->text)]);
                $this->remember('file_updates', $u->id, $m);
            }
            $this->ok('file_updates');
        }
        foreach ($this->rows('file_events', 'at') as $e) {
            if (($fid = $this->map('files', $e->file_id)) && ! $this->map('file_events', (string) $e->id)) {
                $m = FileEvent::create(['file_id' => $fid, 'event' => mb_substr((string) $e->kind, 0, 40), 'location' => mb_substr((string) $e->val, 0, 120), 'happened_at' => self::ts($e->at) ?? now(), 'user_id' => $this->map('profiles', $e->by_id)]);
                $this->remember('file_events', (string) $e->id, $m);
            }
            $this->ok('file_events');
        }
    }

    /** Copies files from Supabase Storage to the private disk. */
    private function importDocuments(): void
    {
        $base = rtrim((string) config('crm.legacy.storage_url'), '/');
        $key = config('crm.legacy.storage_key');
        foreach ($this->rows('documents') as $d) {
            try {
                if ($this->map('documents', $d->id)) {
                    $this->ok('documents');
                    continue;
                }
                $fid = $this->map('files', $d->file_id) ?? throw new \RuntimeException('file not imported');
                if (! $base || ! $key) {
                    throw new \RuntimeException('LEGACY_STORAGE_URL / LEGACY_STORAGE_KEY not set');
                }
                $res = Http::withToken($key)->timeout(120)->get($base.'/storage/v1/object/'.ltrim($d->path, '/'));
                if ($res->failed()) {
                    throw new \RuntimeException('download failed ('.$res->status().')');
                }
                $ext = strtolower(pathinfo($d->name ?: $d->path, PATHINFO_EXTENSION)) ?: 'bin';
                $doc = Document::create(['file_id' => $fid, 'client_id' => ShipmentFile::withoutGlobalScopes()->find($fid)->client_id, 'type' => in_array($d->kind, \App\Domain\DocumentService::TYPES, true) ? $d->kind : 'other',
                    'original_name' => $d->name ?: basename($d->path), 'path' => '', 'mime' => $res->header('Content-Type') ?: 'application/octet-stream', 'size' => strlen($res->body()),
                    'uploaded_by' => $this->map('profiles', $d->uploaded_by) ?? $this->system->id]);
                $path = "documents/{$fid}/{$doc->id}-legacy.{$ext}";
                Storage::disk('private')->put($path, $res->body());
                $doc->update(['path' => $path]);
                $this->remember('documents', $d->id, $doc);
                $this->ok('documents');
            } catch (Throwable $e) {
                $this->bad('documents', $d->id, $e->getMessage());
            }
        }
    }

    private function importRates(): void
    {
        foreach ($this->rows('exchange_rates', 'currency') as $r) {
            if ($r->currency === 'USD' || ! (float) $r->per_usd) {
                continue;
            }
            ExchangeRate::updateOrCreate(['currency' => strtoupper($r->currency)], ['rate_per_usd' => (float) $r->per_usd, 'locked' => (bool) $r->locked, 'source' => 'legacy']);
            $this->ok('exchange_rates');
        }
        $s = $this->old->table('settings')->first();
        $this->count('settings', $s ? 1 : 0);
        if ($s && $s->min_margin !== null) {
            \App\Support\Settings::set('min_margin_usd', (float) $s->min_margin);
            $this->ok('settings');
        }
    }

    private function importCounters(): void
    {
        foreach ($this->rows('ref_counters', 'key') as $c) {
            $row = RefCounter::firstOrNew(['key' => 'legacy:'.$c->key]);
            $row->value = (int) $c->n;
            $row->save();
            $this->ok('ref_counters');
        }
        // Continue each salesperson's reference numbers after the highest imported one.
        foreach (ShipmentFile::withoutGlobalScopes()->pluck('ref') as $ref) {
            if (preg_match('/^([A-Z]{2,4})-(\d{4})-(\d+)$/', $ref, $m)) {
                $row = RefCounter::firstOrNew(['key' => "ref:{$m[1]}:{$m[2]}"]);
                $row->value = max((int) $row->value, (int) $m[3]);
                $row->save();
            }
        }
    }
}
