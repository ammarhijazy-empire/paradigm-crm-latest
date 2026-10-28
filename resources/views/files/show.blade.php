@extends('layouts.app')
@section('title', $file->number)
@section('content')
@php
    $u = auth()->user();
    $blockers = $file->docChecks->where('severity', 'blocker')->where('status', 'open')->count();
    $tabs = ['overview' => 'files.t_overview', 'rfq' => 'files.t_rfq', 'pricing' => 'files.t_pricing', 'quote' => 'files.t_quote', 'shipping' => 'files.t_shipping', 'docs' => 'files.t_docs'];
    if ($u->can('viewInvoices', $file) || $u->can('viewMoney', $file) || $u->can('operate', $file)) { $tabs['money'] = 'files.t_money'; }
    $tabs['history'] = 'files.t_history';
    if (! isset($tabs[$tab])) { $tab = 'overview'; }
@endphp
<div class="mb-2"><a href="{{ route('files.index') }}" class="small">← {{ __('files.title') }}</a></div>
<div class="page-head">
    <div><h1><span class="mono">{{ $file->number }}</span> · {{ $file->client->company }}</h1>
        <div class="sub"><span class="mono">{{ $file->ref }}</span> · {{ __('services.'.$file->service) }} · {{ port_name($file->origin) }} → {{ port_name($file->destination) }} · {{ $file->incoterm }}</div></div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        @if($file->status === 'lost')<x-pill kind="bad">{{ __('files.s_lost') }}</x-pill>@elseif($file->status === 'closed')<x-pill kind="ok">{{ __('files.s_closed') }}</x-pill>@else<x-stage :n="$file->stage" />@endif
        <x-pill :kind="['draft' => '', 'pending' => 'warn', 'review' => 'info', 'priced' => 'ok', 'quoted' => 'info', 'won' => 'ok', 'lost' => 'bad'][$file->pricing_status] ?? ''">{{ __('pricing.st_'.$file->pricing_status) }}</x-pill>
        @if ($blockers)<x-pill kind="bad">{{ __('docs.blockers_n', ['n' => $blockers]) }}</x-pill>@endif
    </div>
</div>
<div class="stepper mb-3">
    @foreach (App\Domain\Workflow::STAGES as $n => $s)
        <div class="step {{ $file->stage > $n || $file->status === 'closed' ? 'done' : ($file->stage === $n && $file->status === 'open' ? 'current' : '') }}">
            <div class="n">{{ $n }}</div><div class="t">{{ __('stages.'.$n) }}</div><div class="o">{{ __('roles.'.$s['owner']) }}</div>
        </div>
    @endforeach
</div>
<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach ($tabs as $k => $label)
        <li class="nav-item"><a class="nav-link {{ $tab === $k ? 'active' : '' }}" href="{{ route('files.show', $file) }}?tab={{ $k }}" @if($tab === $k) aria-current="page" @endif>{{ __($label) }}@if($k === 'docs' && $blockers) <span class="pill bad">{{ $blockers }}</span>@endif</a></li>
    @endforeach
</ul>
@include('files.tabs.'.$tab)
@endsection
