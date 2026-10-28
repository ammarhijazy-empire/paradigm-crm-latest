@extends('layouts.app')
@section('title', $invoice->number)
@section('content')
@php $u = auth()->user(); $canEdit = $u->can('update', $invoice); @endphp
<div class="mb-2"><a href="{{ $u->can('viewAny', App\Models\Invoice::class) ? route('invoices.index') : route('files.show', $invoice->file).'?tab=money' }}" class="small">← {{ __('invoice.title') }}</a></div>
<x-page-head :title="$invoice->number" :sub="$invoice->file->number.' · '.$invoice->client->company">
    <x-slot:actions>
        <x-pill :kind="['paid' => 'ok', 'partly_paid' => 'warn', 'issued' => $invoice->isOverdue() ? 'bad' : '', 'cancelled' => ''][$invoice->status] ?? ''">{{ $invoice->isOverdue() ? __('invoice.overdue') : __('invoice.s_'.$invoice->status) }}</x-pill>
        <a class="btn btn-sm btn-light" href="{{ route('invoices.pdf', $invoice) }}?lang=ar" target="_blank" rel="noopener"><x-icon name="pdf" /> PDF عربي</a>
        <a class="btn btn-sm btn-light" href="{{ route('invoices.pdf', $invoice) }}?lang=en" target="_blank" rel="noopener"><x-icon name="pdf" /> PDF English</a>
    </x-slot:actions>
</x-page-head>
<div class="grid g-main">
    <div>@include('invoices.paper', ['invoice' => $invoice])</div>
    <div class="stack">
        @if ($canEdit && $invoice->status !== 'cancelled')
            @if ($invoice->balance() > 0)
            <x-panel :title="__('invoice.record_payment')">
                <form method="POST" action="{{ route('invoices.payment', $invoice) }}">@csrf
                    <label class="form-label" for="p-amount">{{ __('common.amount') }} ({{ $invoice->currency }})</label><input id="p-amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance() }}" class="form-control num" value="{{ $invoice->balance() }}" required>
                    <label class="form-label mt-2" for="p-date">{{ __('common.date') }}</label><input id="p-date" name="paid_at" type="date" class="form-control" value="{{ today()->toDateString() }}" required>
                    <label class="form-label mt-2" for="p-method">{{ __('invoice.method') }}</label><select id="p-method" name="method" class="form-select"><option value="bank">{{ __('invoice.m_bank') }}</option><option value="cash">{{ __('invoice.m_cash') }}</option><option value="cheque">{{ __('invoice.m_cheque') }}</option></select>
                    <label class="form-label mt-2" for="p-ref">{{ __('invoice.reference') }}</label><input id="p-ref" name="reference" class="form-control">
                    <button class="btn btn-primary btn-sm mt-3">{{ __('common.save') }}</button>
                </form>
            </x-panel>
            @endif
            <x-panel :title="__('invoice.e_invoice')">
                <form method="POST" action="{{ route('invoices.einvoice', $invoice) }}" class="d-flex gap-2">@csrf @method('PUT')
                    <input name="e_invoice_no" class="form-control form-control-sm mono" value="{{ $invoice->e_invoice_no }}" aria-label="{{ __('invoice.e_invoice') }}"><button class="btn btn-sm btn-light">{{ __('common.save') }}</button></form>
            </x-panel>
        @endif
        <x-panel :title="__('invoice.payments')">
            @forelse ($invoice->payments as $p)
                <div class="split py-2 border-bottom small"><span>{{ d($p->paid_at) }} · {{ $p->method ? __('invoice.m_'.$p->method) : '' }} {{ $p->reference }}</span><span class="ltr fw-semibold">{{ money($p->amount, $invoice->currency) }}</span></div>
            @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
            <div class="split mt-2 fw-semibold"><span>{{ __('invoice.balance') }}</span><span class="ltr">{{ money($invoice->balance(), $invoice->currency) }}</span></div>
        </x-panel>
        @if ($canEdit && (float) $invoice->paid == 0 && $invoice->status !== 'cancelled')
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}">@csrf<button class="btn btn-outline-danger btn-sm" data-confirm="{{ __('invoice.cancel_confirm') }}">{{ __('invoice.cancel') }}</button></form>
        @endif
    </div>
</div>
@endsection
