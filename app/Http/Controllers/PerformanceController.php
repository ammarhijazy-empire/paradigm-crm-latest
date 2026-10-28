<?php

namespace App\Http\Controllers;

use App\Domain\PerformanceService;
use App\Models\PerfMgr;
use App\Models\PerfSelf;
use App\Models\User;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function index(Request $request, PerformanceService $perf)
    {
        $u = $request->user();
        abort_unless($u->actsAs('sales'), 403);
        $period = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('period')) ? $request->input('period') : now()->format('Y-m');
        $users = $u->isManagement() ? User::role('sales')->where('active', true)->get() : collect([$u]);
        return view('performance.index', ['rows' => $users->map(fn ($s) => $perf->forUser($s, $period)), 'period' => $period]);
    }

    public function self(Request $request)
    {
        abort_unless($request->user()->hasRole('sales'), 403);
        $d = $request->validate(['period' => ['required', 'regex:/^\d{4}-\d{2}$/'], 'wins' => ['required', 'string', 'max:2000'], 'blockers' => ['required', 'string', 'max:2000'], 'next' => ['required', 'string', 'max:2000'], 'score' => ['required', 'integer', 'between:1,5']]);
        PerfSelf::updateOrCreate(['user_id' => $request->user()->id, 'period' => $d['period']], ['answers' => collect($d)->except('period')->all()]);
        return $this->ok('common.saved');
    }

    public function manager(Request $request, User $user)
    {
        abort_unless($request->user()->isManagement(), 403);
        $d = $request->validate(['period' => ['required', 'regex:/^\d{4}-\d{2}$/'], 'score' => ['required', 'integer', 'between:1,5'], 'comments' => ['nullable', 'string', 'max:3000']]);
        PerfMgr::updateOrCreate(['user_id' => $user->id, 'period' => $d['period']], ['manager_id' => $request->user()->id, 'score' => $d['score'], 'comments' => $d['comments'] ?? null]);
        return $this->ok('common.saved');
    }
}
