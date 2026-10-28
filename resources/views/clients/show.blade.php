@extends('layouts.app')
@section('title', $client->company)
@section('content')
@php $u = auth()->user(); @endphp
<div class="mb-2"><a href="{{ route('clients.index') }}" class="small">← {{ __('clients.title') }}</a></div>
<x-page-head :title="$client->company" :sub="($client->sector === 'other' ? $client->sector_other : __('sectors.'.$client->sector)).($client->products ? ' · '.$client->products : '')">
    <x-slot:actions>
        <x-pill :kind="['lead' => 'info', 'contacted' => 'warn', 'active' => 'ok', 'repeat' => 'rust', 'lost' => 'bad'][$client->status]">{{ __('clients.s_'.$client->status) }}</x-pill>
        @can('update', $client)<a class="btn btn-sm btn-light" href="{{ route('clients.edit', $client) }}">{{ __('common.edit') }}</a>@endcan
        @can('create', App\Models\ShipmentFile::class)<a class="btn btn-sm btn-primary" href="{{ route('rfq.create', ['client' => $client->id]) }}"><x-icon name="plus" /> {{ __('nav.rfq') }}</a>@endcan
    </x-slot:actions>
</x-page-head>
<div class="grid g-main">
    <div class="stack">
        <x-panel :title="__('clients.files')" flush>@include('partials.file-table', ['files' => $client->files->load('client', 'sales')])</x-panel>
        <x-panel :title="__('clients.activities')">
            @can('update', $client)
                <form method="POST" action="{{ route('clients.activity', $client) }}" class="mb-3">@csrf
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <select name="type" class="form-select form-select-sm" style="max-width:160px" aria-label="{{ __('clients.activity_type') }}">@foreach (['call', 'whatsapp', 'meeting', 'email', 'note'] as $t)<option value="{{ $t }}">{{ __('clients.a_'.$t) }}</option>@endforeach</select>
                        <select name="rating" class="form-select form-select-sm" style="max-width:120px" aria-label="{{ __('clients.rating') }}"><option value="">{{ __('clients.rating') }}</option>@for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor</select>
                        <input type="date" name="next_follow_up" class="form-control form-control-sm" style="max-width:170px" aria-label="{{ __('clients.next_follow_up') }}" title="{{ __('clients.next_follow_up') }}">
                    </div>
                    <textarea name="body" class="form-control" rows="2" required aria-label="{{ __('common.notes') }}"></textarea>
                    <button class="btn btn-sm btn-primary mt-2">{{ __('clients.log_activity') }}</button>
                </form>
            @endcan
            @forelse ($client->activities as $a)
                <div class="tl-item"><x-avatar :user="$a->user" /><div style="min-width:0"><div><x-pill>{{ __('clients.a_'.$a->type) }}</x-pill>@if($a->rating) <span class="small-label">{{ str_repeat('★', $a->rating) }}</span>@endif</div><div class="mt-1 text-pre">{{ $a->body }}</div><div class="small-label">{{ d($a->happened_at, true) }}</div></div></div>
            @empty <div class="empty">{{ __('common.none') }}</div> @endforelse
        </x-panel>
    </div>
    <div class="stack">
        <x-panel :title="__('clients.contact')">
            <dl class="dl">
                <dt>{{ __('clients.contact_person') }}</dt><dd>{{ $client->contact_person }}</dd>
                <dt>{{ __('clients.phone') }}</dt><dd class="ltr">{{ $client->phone }}</dd>
                <dt>{{ __('clients.email') }}</dt><dd class="ltr">{{ $client->email }}</dd>
                <dt>{{ __('clients.owner') }}</dt><dd><x-avatar :user="$client->owner" /> {{ $client->owner->name }}</dd>
                <dt>{{ __('clients.source') }}</dt><dd>{{ $client->source ?: '—' }}</dd>
                <dt>{{ __('clients.current_forwarder') }}</dt><dd>{{ $client->current_forwarder ?: '—' }}</dd>
                <dt>{{ __('clients.next_follow_up') }}</dt><dd class="num">{{ d($client->next_follow_up) }}</dd>
                <dt>{{ __('clients.next_order') }}</dt><dd class="num">{{ d($client->next_order_date) }}</dd>
                <dt>{{ __('clients.credit') }}</dt><dd>{{ $client->credit_days ? money($client->credit_limit).' · '.$client->credit_days.' '.__('common.days') : '—' }}</dd>
                <dt>{{ __('clients.tax_id') }}</dt><dd class="mono">{{ $client->tax_id ?: '—' }}</dd>
            </dl>
        </x-panel>
        @if ($client->invoices->isNotEmpty())<x-panel :title="__('invoice.title')" flush>@include('partials.invoice-table', ['invoices' => $client->invoices->load('client', 'file')])</x-panel>@endif
    </div>
</div>
@endsection
