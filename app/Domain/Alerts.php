<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Mail\OutboxMailer;
use App\Models\Client;
use App\Models\CostLine;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Support\Dates;
use App\Support\Settings;

/** Everything the scheduler checks. Each method returns how many alerts it sent. */
class Alerts
{
    public function __construct(private OutboxMailer $mailer, private PerformanceService $perf) {}

    public function followUps(): int
    {
        $n = 0;
        foreach (Client::withoutGlobalScopes()->whereNull('deleted_at')->whereDate('next_follow_up', Dates::today()->toDateString())->get() as $c) {
            event(new CrmEvent('follow_up_due', ['company' => $c->company], route('clients.show', $c), [], [$c->owner_id], null, false));
            $n++;
        }
        return $n;
    }

    public function leadSla(): int
    {
        $n = 0;
        foreach (Lead::whereNull('contacted_at')->whereNull('sla_alerted_at')->where('sla_due_at', '<', now())->get() as $l) {
            event(new CrmEvent('lead_sla', ['company' => $l->company], route('leads.index'), ['admin'], array_filter([$l->assigned_to])));
            $l->update(['sla_alerted_at' => now()]);
            $n++;
        }
        return $n;
    }

    public function invoices(): int
    {
        $n = 0;
        $open = Invoice::whereIn('status', ['issued', 'partly_paid'])->get();
        foreach ($open as $i) {
            $days = (int) Dates::today()->diffInDays($i->due_date, false);
            if ($days === 3) {
                event(new CrmEvent('invoice_due', ['number' => $i->number, 'date' => d($i->due_date)], route('invoices.show', $i), ['accounts'], [$i->file?->sales_id], null, false));
                $n++;
            } elseif ($days < 0 && ($days === -1 || $days % 7 === 0)) {
                event(new CrmEvent('invoice_overdue', ['number' => $i->number], route('invoices.show', $i), ['accounts', 'admin'], [$i->file?->sales_id], null, false));
                $n++;
            }
        }
        return $n;
    }

    public function vendorDues(): int
    {
        $n = 0;
        foreach (CostLine::with('vendor', 'file')->whereNotNull('vendor_due_date')->whereNull('vendor_paid_at')->get() as $c) {
            $days = (int) Dates::today()->diffInDays($c->vendor_due_date, false);
            if ($days === 3 || $days === 0 || $days === -1) {
                event(new CrmEvent('vendor_due', ['vendor' => $c->vendor?->name ?? '—', 'ref' => $c->file->number, 'date' => d($c->vendor_due_date)], route('collections.index'), ['accounts'], [], null, $days < 0));
                $n++;
            }
        }
        return $n;
    }

    public function waitingFiles(): int
    {
        $limit = (int) Settings::get('stage_wait_days');
        $n = 0;
        foreach (ShipmentFile::withoutGlobalScopes()->where('status', 'open')->get() as $f) {
            $days = Dates::workingDaysSince($f->stage_entered_at);
            if ($f->stage === 7 && $days >= 2 && ! $f->check('after_sale_call')) {
                event(new CrmEvent('after_sale_due', ['ref' => $f->number], route('files.show', $f), [], [$f->sales_id], null, false));
                $n++;
            } elseif ($days > $limit && ($days - $limit) % 2 === 1) {
                $role = Workflow::owner($f->stage);
                event(new CrmEvent('file_waiting', ['ref' => $f->number, 'stage' => __('stages.'.$f->stage), 'n' => $days], route('files.show', $f), [$role], $role === 'sales' ? [$f->sales_id] : []));
                $n++;
            }
        }
        return $n;
    }

    /** Mid-month: warn salespeople below half their target. */
    public function performance(): int
    {
        $n = 0;
        $period = now(Dates::tz())->format('Y-m');
        foreach (User::role('sales')->where('active', true)->where('sales_target', '>', 0)->get() as $s) {
            $row = $this->perf->forUser($s, $period);
            if (($row['pct'] ?? 0) < 50) {
                event(new CrmEvent('target_behind', ['pct' => $row['pct'] ?? 0], route('performance.index'), [], [$s->id], null, false));
                $n++;
            }
        }
        return $n;
    }

    /** Weekly (Sunday) and monthly summaries by email to management and each salesperson. */
    public function summary(string $kind): int
    {
        $period = $kind === 'monthly' ? now(Dates::tz())->subMonth()->format('Y-m') : now(Dates::tz())->format('Y-m');
        $sales = User::role('sales')->where('active', true)->get();
        $rows = $sales->map(fn ($s) => $this->perf->forUser($s, $period));
        $open = ShipmentFile::withoutGlobalScopes()->where('status', 'open')->get();
        $n = 0;
        foreach (User::where('active', true)->get()->filter(fn ($u) => $u->isManagement() || $u->hasRole('sales')) as $u) {
            $mine = $u->isManagement() ? $rows : $rows->where('user.id', $u->id);
            $lines = $mine->map(fn ($r) => $r['user']->name.': '.number_format($r['profit'], 2).' / '.number_format($r['target'], 2).' USD ('.($r['pct'] ?? '—').'%) · '.__('perf.won', [], $u->locale).' '.$r['files_won'].' · '.__('perf.leads', [], $u->locale).' '.$r['leads'])->all();
            if ($u->isManagement()) {
                $lines[] = __('dash.open_files', [], $u->locale).': '.$open->count();
                foreach (range(1, 7) as $st) {
                    $lines[] = $st.'. '.__('stages.'.$st, [], $u->locale).': '.$open->where('stage', $st)->count();
                }
            }
            $this->mailer->queue(
                to: $u->email,
                subject: ($kind === 'monthly' ? 'Monthly' : 'Weekly').' summary · '.$period,
                view: 'emails.generic',
                data: ['heading' => __($kind === 'monthly' ? 'perf.mgr_review' : 'perf.title', [], $u->locale).' · '.$period, 'lines' => $lines, 'url' => route('performance.index')],
                kind: 'summary', user: null, locale: $u->locale,
            );
            $n++;
        }
        return $n;
    }
}
