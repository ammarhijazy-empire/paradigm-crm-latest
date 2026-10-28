@extends('layouts.app')
@section('title', __('nav.dashboard'))
@section('content')
<x-page-head :title="__('dash.hello', ['name' => auth()->user()->name])" :sub="__('dash.sub_role')" />
<div class="stack">
@isset($queues['sales'])
    @php $q = $queues['sales']; @endphp
    <div class="grid g-4">
        <div class="panel kpi"><div class="lbl">{{ __('dash.my_files') }}</div><div class="val">{{ $q['files']->count() }}</div></div>
        <div class="panel kpi"><div class="lbl">{{ __('dash.followups') }}</div><div class="val {{ $q['followups']->count() ? 'text-warning' : '' }}">{{ $q['followups']->count() }}</div></div>
        <div class="panel kpi"><div class="lbl">{{ __('dash.new_leads') }}</div><div class="val {{ $q['leads']->count() ? 'text-warning' : '' }}">{{ $q['leads']->count() }}</div></div>
        <div class="panel kpi"><div class="lbl">{{ __('dash.after_sale') }}</div><div class="val">{{ $q['afterSale']->count() }}</div></div>
    </div>
    <div class="grid g-main">
        <x-panel :title="__('dash.my_queue')" flush>
            <x-slot:actions><a class="btn btn-primary btn-sm" href="{{ route('rfq.create') }}"><x-icon name="plus" /> {{ __('nav.rfq') }}</a></x-slot:actions>
            @include('partials.file-table', ['files' => $q['files'], 'days' => true])
        </x-panel>
        <div class="stack">
            <x-panel :title="__('dash.followups')">
                @forelse ($q['followups'] as $c)
                    <div class="split py-2 border-bottom"><a class="row-link" href="{{ route('clients.show', $c) }}">{{ $c->company }}</a><span class="small num">{{ d($c->next_follow_up) }}</span></div>
                @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
            </x-panel>
            <x-panel :title="__('dash.new_leads')">
                @forelse ($q['leads'] as $l)
                    <div class="split py-2 border-bottom"><a class="row-link" href="{{ route('leads.index') }}">{{ $l->company }}</a>@if($l->sla_due_at->isPast())<x-pill kind="bad">{{ __('leads.late') }}</x-pill>@else<x-pill kind="warn">{{ $l->sla_due_at->diffForHumans() }}</x-pill>@endif</div>
                @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
            </x-panel>
        </div>
    </div>
@endisset
@isset($queues['pricing'])
    <x-panel :title="__('dash.to_price')" flush>
        <x-slot:actions>@if($queues['pricing']['emails'])<a href="{{ route('pricing.index') }}"><x-pill kind="warn">{{ __('dash.emails_to_match', ['n' => $queues['pricing']['emails']]) }}</x-pill></a>@endif</x-slot:actions>
        @include('partials.file-table', ['files' => $queues['pricing']['files'], 'days' => true])
    </x-panel>
@endisset
@isset($queues['pricing_review'])
    <x-panel :title="__('dash.to_review')" flush>@include('partials.file-table', ['files' => $queues['pricing_review']['files'], 'days' => true])</x-panel>
@endisset
@isset($queues['ops'])
    <x-panel :title="__('dash.ops_queue')" flush>@include('partials.file-table', ['files' => $queues['ops']['files'], 'days' => true])</x-panel>
    <x-panel :title="__('dash.arriving')" flush>@include('partials.file-table', ['files' => $queues['ops']['arriving']])</x-panel>
@endisset
@isset($queues['customs'])
    <x-panel :title="__('dash.customs_queue')" flush>@include('partials.file-table', ['files' => $queues['customs']['files'], 'days' => true])</x-panel>
    <x-panel :title="__('dash.arriving')" flush>@include('partials.file-table', ['files' => $queues['customs']['arriving']])</x-panel>
@endisset
@isset($queues['accounts'])
    @php $q = $queues['accounts']; @endphp
    <div class="grid g-3">
        <div class="panel kpi"><div class="lbl">{{ __('dash.pay_terms') }}</div><div class="val">{{ $q['payTerms']->count() }}</div></div>
        <div class="panel kpi"><div class="lbl">{{ __('dash.invoices_due') }}</div><div class="val">{{ $q['invoices']->count() }}</div></div>
        <div class="panel kpi"><div class="lbl">{{ __('dash.vendor_dues') }}</div><div class="val {{ $q['vendorDues']->count() ? 'text-warning' : '' }}">{{ $q['vendorDues']->count() }}</div></div>
    </div>
    <div class="grid g-2">
        <x-panel :title="__('dash.pay_terms')">
            @forelse ($q['payTerms'] as $t)
                <div class="split py-2 border-bottom"><a class="row-link mono" href="{{ route('files.show', $t->file) }}?tab=pricing">{{ $t->file->number }}</a><span class="small">{{ __('pay.'.$t->method) }}{{ $t->credit_days ? ' · '.$t->credit_days.' '.__('common.days') : '' }}</span></div>
            @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
        </x-panel>
        <x-panel :title="__('dash.accounts_queue')" flush>@include('partials.file-table', ['files' => $q['files']])</x-panel>
    </div>
@endisset
</div>
@endsection
