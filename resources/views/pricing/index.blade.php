@extends('layouts.app')
@section('title', __('nav.pricing'))
@section('content')
<x-page-head :title="__('pricing.desk')" :sub="__('pricing.desk_sub')" />
@php $u = auth()->user(); @endphp
<div class="stack">
@if ($u->actsAs('pricing'))
    <x-panel :title="__('pricing.to_price')" flush>
        <x-slot:actions><x-pill>{{ $toPrice->count() }}</x-pill></x-slot:actions>
        @include('pricing.partials.req-table', ['list' => $toPrice])
    </x-panel>
    @if ($reprice->isNotEmpty())
    <x-panel :title="__('pricing.reprice_requests')" flush>
        <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('files.file') }}</th><th>{{ __('pricing.reason') }}</th><th>{{ __('pricing.target') }}</th><th>{{ __('common.date') }}</th></tr></thead><tbody>
        @foreach ($reprice as $r)<tr><td><a class="row-link mono" href="{{ route('files.show', $r->file) }}?tab=pricing">{{ $r->file->ref }}</a></td><td class="small">{{ $r->reason }}</td><td class="ltr">{{ $r->target_usd ? money($r->target_usd) : '—' }}</td><td class="num small">{{ d($r->created_at) }}</td></tr>@endforeach
        </tbody></table></div>
    </x-panel>
    @endif
    <x-panel flush>
        <x-slot:heading>{{ __('pricing.inbox') }} <x-pill kind="info">{{ __('ai.suggests') }}</x-pill></x-slot:heading>
        <x-slot:actions><span class="small-label mono">{{ config('crm.rates_mailbox.username') ?: __('pricing.mailbox_off') }}</span></x-slot:actions>
        @if ($inbox->isEmpty())
            <div class="empty">{{ __('common.none') }}</div>
        @else
        <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('pricing.from') }}</th><th>{{ __('pricing.subject') }}</th><th>{{ __('pricing.matched_to') }}</th><th>{{ __('common.status') }}</th></tr></thead><tbody>
        @foreach ($inbox as $m)
            <tr><td>{{ $m->vendor?->name ?? $m->from_email }}<div class="small-label">{{ d($m->received_at, true) }}</div></td>
                <td class="small mono" style="min-width:200px">{{ Str::limit($m->subject, 80) }}@if($m->attachments)<div class="small-label">📎 {{ count($m->attachments) }}</div>@endif</td>
                <td>
                    @if ($m->file)<a class="mono" href="{{ route('files.show', $m->file) }}?tab=pricing">{{ $m->file->ref }}</a> <span class="small-label">{{ __('pricing.m_'.$m->match_method) }}</span>
                    @else
                        <form method="POST" action="{{ route('pricing.inbound.match', $m) }}" class="d-flex gap-1 flex-wrap">@csrf
                            <select name="file_id" class="form-select form-select-sm" required aria-label="{{ __('pricing.matched_to') }}"><option value="">{{ __('pricing.needs_matching') }}</option>@foreach ($matchable as $f)<option value="{{ $f->id }}">{{ $f->ref }}</option>@endforeach</select>
                            <select name="vendor_id" class="form-select form-select-sm" required aria-label="{{ __('pricing.vendor') }}"><option value="">{{ __('pricing.vendor') }}</option>@foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($m->vendor_id === $v->id)>{{ $v->name }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-light">{{ __('common.save') }}</button></form>
                    @endif
                </td>
                <td><x-pill :kind="['read' => 'info ai', 'unmatched' => 'warn', 'failed' => 'bad', 'reading' => 'info', 'new' => ''][$m->status] ?? ''">{{ __('pricing.in_'.$m->status) }}</x-pill></td></tr>
        @endforeach
        </tbody></table></div>
        @endif
    </x-panel>
@endif
@if ($u->actsAs('pricing_review'))
    <x-panel :title="__('pricing.waiting_review')" flush><x-slot:actions><x-pill>{{ $review->count() }}</x-pill></x-slot:actions>@include('pricing.partials.req-table', ['list' => $review])</x-panel>
@endif
</div>
@endsection
