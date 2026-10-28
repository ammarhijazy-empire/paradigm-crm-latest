@extends('layouts.app')
@section('title', __('nav.collections'))
@section('content')
<x-page-head :title="__('invoice.collections')" />
<div class="stack">
    <x-panel :title="__('invoice.to_collect')" flush>
        <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('invoice.number') }}</th><th>{{ __('files.client') }}</th><th>{{ __('invoice.balance') }}</th><th>{{ __('invoice.due_date') }}</th><th></th></tr></thead><tbody>
        @forelse ($invoices as $i)
            @php $n = today()->diffInDays($i->due_date, false); @endphp
            <tr><td><a class="row-link mono" href="{{ route('invoices.show', $i) }}">{{ $i->number }}</a><div class="small-label mono">{{ $i->file->number }}</div></td><td class="small">{{ $i->client->company }}</td><td class="ltr">{{ money($i->balance(), $i->currency) }}</td><td class="num">{{ d($i->due_date) }}</td>
                <td>@if($n < 0)<x-pill kind="bad">{{ __('invoice.days_late', ['n' => (int) abs($n)]) }}</x-pill>@elseif($n <= 3)<x-pill kind="warn">{{ __('invoice.days_left', ['n' => (int) $n]) }}</x-pill>@else<span class="small-label">{{ __('invoice.days_left', ['n' => (int) $n]) }}</span>@endif</td></tr>
        @empty <tr><td colspan="5" class="empty">{{ __('common.none') }}</td></tr> @endforelse
        </tbody></table></div>
    </x-panel>
    <x-panel :title="__('invoice.vendor_dues')" flush>
        <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('pricing.vendor') }}</th><th>{{ __('files.file') }}</th><th>{{ __('pricing.line') }}</th><th>{{ __('common.amount') }}</th><th>{{ __('invoice.due_date') }}</th><th></th></tr></thead><tbody>
        @forelse ($dues as $c)
            <tr><td>{{ $c->vendor?->name ?? '—' }}</td><td><a class="mono" href="{{ route('files.show', $c->file) }}?tab=money">{{ $c->file->number }}</a></td><td class="small">{{ __('costs.'.$c->type) }}</td><td class="ltr">{{ money($c->actual ?? $c->estimated, $c->currency) }}</td>
                <td class="num">@if($c->vendor_due_date->isPast())<x-pill kind="bad">{{ d($c->vendor_due_date) }}</x-pill>@else{{ d($c->vendor_due_date) }}@endif</td>
                <td><form method="POST" action="{{ route('collections.vendor-paid', $c) }}">@csrf<button class="btn btn-sm btn-light">{{ __('invoice.mark_paid') }}</button></form></td></tr>
        @empty <tr><td colspan="6" class="empty">{{ __('common.none') }}</td></tr> @endforelse
        </tbody></table></div>
    </x-panel>
</div>
@endsection
