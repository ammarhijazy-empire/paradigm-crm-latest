<?php

namespace App\Http\Controllers;

use App\Ai\AiGuard;
use App\Domain\MarginService;
use App\Models\Client;
use App\Models\CostLine;
use App\Models\FilePayTerm;
use App\Models\Invoice;
use App\Models\InboundEmail;
use App\Models\Lead;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Support\Dates;
use App\Support\Money;
use App\Support\Settings;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MarginService $margin, AiGuard $ai)
    {
        $u = $request->user();
        $open = ShipmentFile::with('client', 'sales')->where('status', 'open')->get();
        $waitDays = (int) Settings::get('stage_wait_days');
        $waiting = $open->filter(fn ($f) => Dates::workingDaysSince($f->stage_entered_at) > $waitDays)->sortBy('stage_entered_at')->values();
        $data = compact('open', 'waiting', 'waitDays');

        if ($u->isManagement()) {
            $start = now()->startOfMonth();
            $closedThisMonth = ShipmentFile::with('costLines', 'invoices')->whereHas('invoices', fn ($q) => $q->where('issue_date', '>=', $start))->get();
            $profitBy = User::role('sales')->where('active', true)->get()->map(function ($s) use ($closedThisMonth, $margin) {
                $p = $closedThisMonth->where('sales_id', $s->id)->sum(fn ($f) => $margin->actualProfitUsd($f) ?? 0);
                return ['user' => $s, 'profit' => $p, 'target' => (float) $s->sales_target];
            });
            $data += [
                'profit' => $profitBy->sum('profit'),
                'goal' => $profitBy->sum('target'),
                'profitBy' => $profitBy,
                'toCollect' => Invoice::whereIn('status', ['issued', 'partly_paid'])->get()->sum(fn ($i) => Money::toUsd($i->balance(), $i->currency)),
                'overdue' => Invoice::whereIn('status', ['issued', 'partly_paid'])->where('due_date', '<', today())->get()->sum(fn ($i) => Money::toUsd($i->balance(), $i->currency)),
                'pipeline' => collect(range(1, 7))->mapWithKeys(fn ($n) => [$n => $open->where('stage', $n)->count()]),
                'lost' => ShipmentFile::where('status', 'lost')->where('updated_at', '>=', now()->startOfQuarter())->selectRaw('lost_category, count(*) as n')->groupBy('lost_category')->orderByDesc('n')->pluck('n', 'lost_category'),
                'marginApprovals' => $open->filter(fn ($f) => $f->stage === 2 && ! $f->margin_approved_at && $f->quoteLines()->exists() && $margin->belowMinimum($f->load('quoteLines', 'costLines'))),
                'aiSpent' => $ai->spentThisMonth(), 'aiLimit' => $ai->limit(),
            ];
            return view('dashboard.admin', $data);
        }

        $roleQueues = [];
        if ($u->hasRole('sales')) {
            $roleQueues['sales'] = [
                'files' => $open->where('sales_id', $u->id)->values(),
                'followups' => Client::whereNotNull('next_follow_up')->where('next_follow_up', '<=', today())->orderBy('next_follow_up')->get(),
                'leads' => Lead::where('assigned_to', $u->id)->whereNull('contacted_at')->latest('received_at')->get(),
                'afterSale' => $open->where('sales_id', $u->id)->where('stage', 7)->values(),
            ];
        }
        if ($u->hasRole('pricing')) {
            $roleQueues['pricing'] = ['files' => $open->where('pricing_status', 'pending')->values(), 'emails' => InboundEmail::whereIn('status', ['unmatched', 'failed'])->count()];
        }
        if ($u->hasRole('pricing_review')) {
            $roleQueues['pricing_review'] = ['files' => $open->where('pricing_status', 'review')->values()];
        }
        if ($u->hasRole('ops')) {
            $roleQueues['ops'] = ['files' => $open->whereIn('stage', [3, 4])->values(), 'arriving' => $open->filter(fn ($f) => $f->eta && $f->eta->between(today(), today()->addDays(7)))->values()];
        }
        if ($u->hasRole('customs')) {
            $roleQueues['customs'] = ['files' => $open->where('stage', 5)->values(), 'arriving' => $open->where('stage', 4)->filter(fn ($f) => $f->eta && $f->eta->lte(today()->addDays(7)))->values()];
        }
        if ($u->hasRole('accounts')) {
            $roleQueues['accounts'] = [
                'files' => $open->filter(fn ($f) => $f->stage === 6 || ($f->stage === 5 && ! $f->check('release_approved')))->values(),
                'payTerms' => FilePayTerm::with('file')->where('status', 'pending')->get(),
                'invoices' => Invoice::with('client')->whereIn('status', ['issued', 'partly_paid'])->where('due_date', '<=', today()->addDays(7))->orderBy('due_date')->get(),
                'vendorDues' => CostLine::with('vendor', 'file')->whereNotNull('vendor_due_date')->whereNull('vendor_paid_at')->where('vendor_due_date', '<=', today()->addDays(7))->orderBy('vendor_due_date')->get(),
            ];
        }
        return view('dashboard.role', $data + ['queues' => $roleQueues]);
    }
}
