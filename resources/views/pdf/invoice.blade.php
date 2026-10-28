@php $co = App\Support\Settings::get('company'); $bank = App\Support\Settings::get('banks')[$invoice->currency] ?? []; $f = $invoice->file; $ar = $locale === 'ar'; $al = $ar ? 'right' : 'left'; $alr = $ar ? 'left' : 'right'; @endphp
<html dir="{{ $ar ? 'rtl' : 'ltr' }}"><head><style>
body { font-family: dejavusans; font-size: 9.5pt; color: #1a1a1a; }
h1 { color: #12303a; font-size: 16pt; margin: 0; }
table { width: 100%; border-collapse: collapse; }
.grid td { padding: 5px 6px; border-bottom: 0.5pt solid #e3e3e3; text-align: {{ $al }}; }
.grid th { background: #f3f5f5; padding: 6px; text-align: {{ $al }}; border-bottom: 0.8pt solid #12303a; }
.num { text-align: {{ $alr }} !important; }
.muted { color: #666; font-size: 8.5pt; }
.box { border: 0.5pt solid #d5d5d5; padding: 8px; }
.words { background: #f6f3ef; padding: 8px; }
</style></head><body>
<table><tr>
<td style="text-align:{{ $al }}"><h1>{{ $co['name'] }}</h1><div class="muted">{{ $co['address'] }}<br>{{ __('invoice.tax_reg') }}: {{ $co['tax_reg_no'] ?: '—' }} · {{ __('invoice.cr_no') }}: {{ $co['commercial_reg_no'] ?: '—' }}</div></td>
<td style="text-align:{{ $alr }}"><h1>{{ __('invoice.tax_invoice') }}</h1>{{ $invoice->number }}<br>{{ __('common.date') }}: {{ d($invoice->issue_date) }}<br>{{ __('invoice.due_date') }}: {{ d($invoice->due_date) }}<br>{{ __('invoice.e_invoice') }}: {{ $invoice->e_invoice_no ?: '—' }}</td>
</tr></table>
<br>
<table class="box"><tr><td style="text-align:{{ $al }}"><b>{{ __('invoice.bill_to') }}:</b> {{ $invoice->client->company }}@if($invoice->client->tax_id) · {{ __('clients.tax_id') }} {{ $invoice->client->tax_id }}@endif<br>{{ $invoice->client->address }}</td>
<td style="text-align:{{ $al }}"><b>{{ __('files.shipment') }}:</b> {{ $f->number }} · {{ port_name($f->origin) }} → {{ port_name($f->destination) }}@if($f->bl_no)<br>{{ __('files.bl') }}: {{ $f->bl_no }}@endif</td></tr></table>
<br>
<table class="grid"><tr><th>{{ __('invoice.description') }}</th><th class="num">{{ __('invoice.qty') }}</th><th class="num">{{ __('invoice.unit_price') }}</th><th class="num">{{ __('invoice.vat') }}</th><th class="num">{{ __('common.amount') }}</th></tr>
@foreach ($invoice->lines as $l)<tr><td>{{ $ar ? $l->description_ar : $l->description_en }}</td><td class="num">{{ (float) $l->qty }}</td><td class="num">{{ number_format($l->unit_price, 2) }}</td><td class="num">{{ $l->vat_applies ? number_format($l->vat_amount, 2) : __('invoice.exempt') }}</td><td class="num">{{ number_format($l->amount, 2) }}</td></tr>@endforeach
</table>
<table style="margin-top:8px"><tr><td style="width:55%"></td><td>
<table class="grid"><tr><td>{{ __('invoice.subtotal') }}</td><td class="num">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td></tr>
<tr><td>{{ __('invoice.vat') }}</td><td class="num">{{ number_format($invoice->vat_total, 2) }} {{ $invoice->currency }}</td></tr>
<tr><td><b>{{ __('invoice.total') }}</b></td><td class="num"><b>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</b></td></tr></table></td></tr></table>
<br>
<div class="words"><b>{{ __('invoice.in_words') }}:</b><br><span dir="ltr">{{ App\Support\MoneyWords::en((float) $invoice->total, $invoice->currency) }}</span><br><span dir="rtl">{{ App\Support\MoneyWords::ar((float) $invoice->total, $invoice->currency) }}</span></div>
<br>
<div class="box"><b>{{ __('invoice.bank') }} ({{ $invoice->currency }})</b><br><span dir="ltr">{{ collect($bank)->filter()->map(fn ($v, $k) => ucfirst($k).': '.$v)->join(' · ') ?: '—' }}</span></div>
<p class="muted" style="text-align:center">{{ __('invoice.footer') }}</p>
</body></html>
