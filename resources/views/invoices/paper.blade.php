@php $co = App\Support\Settings::get('company'); $bank = App\Support\Settings::get('banks')[$invoice->currency] ?? null; $f = $invoice->file; @endphp
<div class="paper">
    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
        <div><div class="d-flex align-items-center gap-2"><span class="brand-mark">PC</span><span class="t fw-bold fs-5">{{ $co['name'] }}</span></div>
            <div class="small mt-2">{{ $co['address'] }}<br>{{ __('invoice.tax_reg') }}: <span class="mono">{{ $co['tax_reg_no'] ?: '—' }}</span><br>{{ __('invoice.cr_no') }}: <span class="mono">{{ $co['commercial_reg_no'] ?: '—' }}</span></div></div>
        <div class="text-end"><div class="t fw-bold fs-5">{{ __('invoice.tax_invoice') }}</div><div class="small mono">{{ $invoice->number }}</div>
            <div class="small">{{ __('common.date') }}: {{ d($invoice->issue_date) }}<br>{{ __('invoice.due_date') }}: {{ d($invoice->due_date) }}<br>{{ __('invoice.e_invoice') }}: <span class="mono">{{ $invoice->e_invoice_no ?: '—' }}</span></div></div>
    </div>
    <div class="mb-3 small"><strong>{{ __('invoice.bill_to') }}:</strong> {{ $invoice->client->company }}@if($invoice->client->tax_id) · {{ __('clients.tax_id') }} <span class="mono">{{ $invoice->client->tax_id }}</span>@endif<br>
        <strong>{{ __('files.shipment') }}:</strong> <span class="mono">{{ $f->number }}</span> · {{ port_name($f->origin) }} → {{ port_name($f->destination) }}@if($f->bl_no) · {{ __('files.bl') }} <span class="mono">{{ $f->bl_no }}</span>@endif</div>
    <table><thead><tr><th>{{ __('invoice.description') }}</th><th>{{ __('invoice.qty') }}</th><th>{{ __('invoice.unit_price') }}</th><th>{{ __('invoice.vat') }}</th><th>{{ __('common.amount') }}</th></tr></thead><tbody>
        @foreach ($invoice->lines as $l)<tr><td>{{ rtl() ? $l->description_ar : $l->description_en }}</td><td>{{ (float) $l->qty }}</td><td>{{ number_format($l->unit_price, 2) }}</td><td>{{ $l->vat_applies ? number_format($l->vat_amount, 2) : __('invoice.exempt') }}</td><td>{{ number_format($l->amount, 2) }}</td></tr>@endforeach
    </tbody></table>
    <div class="d-flex justify-content-end mt-3"><table style="max-width:340px">
        <tr><td>{{ __('invoice.subtotal') }}</td><td class="text-end">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td></tr>
        <tr><td>{{ __('invoice.vat') }}</td><td class="text-end">{{ number_format($invoice->vat_total, 2) }} {{ $invoice->currency }}</td></tr>
        <tr><th>{{ __('invoice.total') }}</th><th class="text-end">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</th></tr></table></div>
    <div class="words mt-3"><div><strong>{{ __('invoice.in_words') }}:</strong></div><div dir="ltr">{{ App\Support\MoneyWords::en((float) $invoice->total, $invoice->currency) }}</div><div dir="rtl">{{ App\Support\MoneyWords::ar((float) $invoice->total, $invoice->currency) }}</div></div>
    @if ($bank)<div class="bank mt-3"><strong>{{ __('invoice.bank') }} ({{ $invoice->currency }})</strong><div class="ltr">{{ collect($bank)->filter()->map(fn ($v, $k) => ucfirst($k).': '.$v)->join(' · ') ?: '—' }}</div></div>@endif
    <div class="small text-center mt-4" style="color:#666">{{ __('invoice.footer') }}</div>
</div>
