@extends('layouts.app')
@section('title', __('nav.performance'))
@section('content')
@php $u = auth()->user(); @endphp
<x-page-head :title="__('perf.title')" :sub="__('perf.sub')">
    <x-slot:actions><form method="GET"><input type="month" name="period" value="{{ $period }}" class="form-control form-control-sm" data-autosubmit aria-label="{{ __('perf.period') }}"></form></x-slot:actions>
</x-page-head>
<section class="panel mb-3"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('files.sales') }}</th><th>{{ __('perf.target') }}</th><th>{{ __('perf.profit') }}</th><th style="min-width:140px">%</th><th>{{ __('perf.commission') }}</th><th>{{ __('perf.won') }}</th><th>{{ __('perf.open') }}</th><th>{{ __('perf.lost') }}</th><th>{{ __('perf.leads') }}</th><th>{{ __('perf.self') }}</th><th>{{ __('perf.mgr') }}</th></tr></thead><tbody>
@foreach ($rows as $r)
    <tr><td><x-avatar :user="$r['user']" /> {{ $r['user']->name }}</td><td class="ltr">{{ money($r['target']) }}</td><td class="fw-semibold ltr">{{ money($r['profit']) }}</td>
        <td>@if($r['pct'] !== null)<div class="progress" style="height:8px" role="progressbar" aria-valuenow="{{ $r['pct'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ min($r['pct'], 100) }}%"></div></div><span class="small-label num">{{ $r['pct'] }}%</span>@else — @endif</td>
        <td class="ltr">{{ money($r['commission']) }} <span class="small-label">({{ (float) $r['user']->commission_pct }}%)</span></td>
        <td class="num">{{ $r['files_won'] }}</td><td class="num">{{ $r['files_open'] }}</td><td class="num">{{ $r['files_lost'] }}</td><td class="num">{{ $r['leads'] }}@if($r['leads_late']) <x-pill kind="warn">{{ $r['leads_late'] }}</x-pill>@endif</td>
        <td>@if($r['self'])<x-pill kind="ok">{{ $r['self']->answers['score'] ?? '' }}/5</x-pill>@else<x-pill kind="warn">{{ __('perf.pending') }}</x-pill>@endif</td>
        <td>@if($r['mgr'])<x-pill kind="ok">{{ $r['mgr']->score }}/5</x-pill>@else<x-pill kind="warn">{{ __('perf.pending') }}</x-pill>@endif</td></tr>
@endforeach
</tbody></table></div></section>
<div class="grid g-2">
    @if ($u->hasRole('sales'))
        @php $mine = $rows->firstWhere('user.id', $u->id); $a = $mine['self']->answers ?? []; @endphp
        <x-panel :title="__('perf.self_review')">
            <form method="POST" action="{{ route('performance.self') }}">@csrf<input type="hidden" name="period" value="{{ $period }}">
                @foreach (['wins', 'blockers', 'next'] as $k)<label class="form-label mt-2" for="sr-{{ $k }}">{{ __('perf.q_'.$k) }}</label><textarea id="sr-{{ $k }}" name="{{ $k }}" class="form-control" rows="2" required>{{ $a[$k] ?? '' }}</textarea>@endforeach
                <label class="form-label mt-2" for="sr-score">{{ __('perf.score') }}</label><select id="sr-score" name="score" class="form-select" style="max-width:120px">@for ($i = 1; $i <= 5; $i++)<option @selected(($a['score'] ?? 3) == $i)>{{ $i }}</option>@endfor</select>
                <button class="btn btn-primary btn-sm mt-3">{{ __('common.save') }}</button></form>
        </x-panel>
    @endif
    @if ($u->isManagement())
        <x-panel :title="__('perf.mgr_review')">
            @foreach ($rows as $r)
                <form method="POST" action="{{ route('performance.manager', $r['user']) }}" class="py-2 border-bottom">@csrf<input type="hidden" name="period" value="{{ $period }}">
                    <div class="fw-semibold small mb-1">{{ $r['user']->name }}</div>
                    @if($r['self'])<div class="small-label mb-1">{{ Str::limit($r['self']->answers['wins'] ?? '', 140) }}</div>@endif
                    <div class="d-flex gap-2"><select name="score" class="form-select form-select-sm" style="max-width:90px" aria-label="{{ __('perf.score') }}">@for ($i = 1; $i <= 5; $i++)<option @selected(($r['mgr']->score ?? 3) == $i)>{{ $i }}</option>@endfor</select>
                        <input name="comments" class="form-control form-control-sm" value="{{ $r['mgr']->comments ?? '' }}" aria-label="{{ __('common.notes') }}"><button class="btn btn-sm btn-light">{{ __('common.save') }}</button></div>
                </form>
            @endforeach
        </x-panel>
    @endif
</div>
@endsection
