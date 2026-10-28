<?php

namespace App\Domain;

use App\Models\Lead;
use App\Models\PerfMgr;
use App\Models\PerfSelf;
use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Support\Carbon;

class PerformanceService
{
    public function __construct(private MarginService $margin) {}

    public function forUser(User $s, string $period): array
    {
        $from = Carbon::parse($period.'-01', config('app.display_timezone'))->startOfMonth()->utc();
        $to = $from->copy()->addMonth();
        $files = ShipmentFile::withoutGlobalScopes()->with('costLines', 'invoices')->where('sales_id', $s->id)
            ->whereHas('invoices', fn ($q) => $q->whereBetween('issue_date', [$from->toDateString(), $to->copy()->subDay()->toDateString()]))->get();
        $profit = round($files->sum(fn ($f) => $this->margin->actualProfitUsd($f) ?? 0), 2);
        $target = (float) $s->sales_target;
        return [
            'user' => $s, 'period' => $period, 'target' => $target, 'profit' => $profit,
            'pct' => $target > 0 ? round($profit / $target * 100) : null,
            'commission' => round($profit * (float) $s->commission_pct / 100, 2),
            'files_won' => ShipmentFile::withoutGlobalScopes()->where('sales_id', $s->id)->where('pricing_status', 'won')->whereBetween('updated_at', [$from, $to])->count(),
            'files_open' => ShipmentFile::withoutGlobalScopes()->where('sales_id', $s->id)->where('status', 'open')->count(),
            'files_lost' => ShipmentFile::withoutGlobalScopes()->where('sales_id', $s->id)->where('status', 'lost')->whereBetween('updated_at', [$from, $to])->count(),
            'leads' => Lead::where('assigned_to', $s->id)->whereBetween('received_at', [$from, $to])->count(),
            'leads_late' => Lead::where('assigned_to', $s->id)->whereBetween('received_at', [$from, $to])->whereNotNull('sla_alerted_at')->count(),
            'self' => PerfSelf::where('user_id', $s->id)->where('period', $period)->first(),
            'mgr' => PerfMgr::where('user_id', $s->id)->where('period', $period)->first(),
        ];
    }
}
