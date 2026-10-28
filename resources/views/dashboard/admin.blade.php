@extends('layouts.app')
@section('title', __('nav.dashboard'))
@section('content')
<x-page-head :title="__('dash.hello', ['name' => auth()->user()->name])" :sub="__('dash.sub_admin')" />
<div class="grid g-4 mb-3">
    <div class="panel kpi"><div class="lbl">{{ __('dash.open_files') }}</div><div class="val">{{ $open->count() }}</div></div>
    <div class="panel kpi"><div class="lbl">{{ __('dash.profit_month') }}</div><div class="val ltr">{{ money($profit) }}</div><div class="note">{{ __('dash.of_goal', ['goal' => money($goal)]) }}</div></div>
    <div class="panel kpi"><div class="lbl">{{ __('dash.waiting') }}</div><div class="val {{ $waiting->count() ? 'text-warning' : '' }}">{{ $waiting->count() }}</div><div class="note">{{ __('dash.waiting_note', ['n' => $waitDays]) }}</div></div>
    <div class="panel kpi"><div class="lbl">{{ __('dash.to_collect') }}</div><div class="val ltr">{{ money($toCollect) }}</div><div class="note">{{ __('dash.overdue_note', ['amt' => money($overdue)]) }}</div></div>
</div>
<div class="grid g-main mb-3">
    <x-panel :title="__('dash.pipeline')">
        @php $max = max(1, $pipeline->max()); @endphp
        @foreach ($pipeline as $n => $c)
            <a class="pipe-row text-reset text-decoration-none" href="{{ route('files.index', ['stage' => $n]) }}"><span><x-stage :n="$n" /></span><span class="pipe-bar"><span style="width:{{ $c / $max * 100 }}%"></span></span><span class="c">{{ $c }}</span></a>
        @endforeach
    </x-panel>
    <x-panel :title="__('dash.profit_by_rep')">
        @forelse ($profitBy as $row)
            @php $pc = $row['target'] > 0 ? round($row['profit'] / $row['target'] * 100) : 0; @endphp
            <div class="mb-3"><div class="split small"><span><x-avatar :user="$row['user']" /> {{ $row['user']->name }}</span><span class="num ltr">{{ money($row['profit']) }} / {{ money($row['target']) }} · {{ $pc }}%</span></div>
                <div class="progress mt-1" style="height:8px" role="progressbar" aria-valuenow="{{ $pc }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ min($pc, 100) }}%"></div></div></div>
        @empty <div class="empty">{{ __('common.none') }}</div> @endforelse
    </x-panel>
</div>
<div class="mb-3"><x-panel :title="__('dash.waiting_files')" flush>@include('partials.file-table', ['files' => $waiting, 'days' => true])</x-panel></div>
<div class="grid g-3">
    <x-panel :title="__('dash.approvals')">
        @forelse ($marginApprovals as $f)
            <div class="split py-2 border-bottom"><a class="row-link mono" href="{{ route('files.show', $f) }}?tab=quote">{{ $f->number }}</a><span class="small">{{ __('dash.margin_low') }}</span></div>
        @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
    </x-panel>
    <x-panel :title="__('dash.lost_reasons')">
        @php $lmax = max(1, $lost->max() ?? 1); @endphp
        @forelse ($lost as $cat => $n)
            <div class="pipe-row"><span>{{ __('lost.'.$cat) }}</span><span class="pipe-bar"><span style="width:{{ $n / $lmax * 100 }}%;background:var(--bad)"></span></span><span class="c">{{ $n }}</span></div>
        @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
    </x-panel>
    <x-panel :title="__('nav.ai')">
        @php $pc = $aiLimit > 0 ? min(100, round($aiSpent / $aiLimit * 100)) : 0; @endphp
        <div class="split small mb-1"><span>{{ __('ai.spent') }}</span><span class="ltr">{{ money($aiSpent) }} / {{ money($aiLimit) }}</span></div>
        <div class="progress mb-3" style="height:8px"><div class="progress-bar" style="width:{{ $pc }}%"></div></div>
        <a href="{{ route('ai.usage') }}" class="small">{{ __('ai.runs') }} →</a>
    </x-panel>
</div>
@endsection
