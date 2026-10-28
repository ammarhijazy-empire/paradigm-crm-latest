@php $co = App\Support\Settings::get('company'); $q = $file->inq ?? []; $ar = $locale === 'ar'; $al = $ar ? 'right' : 'left'; $alr = $ar ? 'left' : 'right'; @endphp
<html dir="{{ $ar ? 'rtl' : 'ltr' }}"><head><style>
body { font-family: dejavusans; font-size: 10pt; color: #16262b; }
h1 { color: #12303a; font-size: 16pt; margin: 0; }
table { width: 100%; border-collapse: collapse; }
.grid td { padding: 5px 6px; border-bottom: 0.5pt solid #dbe3e4; text-align: {{ $al }}; }
.grid th { background: #12303a; color: #fff; padding: 6px; text-align: {{ $al }}; }
.muted { color: #5d7076; font-size: 8.5pt; }
.box { background: #f3f5f5; padding: 8px; }
.num { text-align: {{ $alr }} !important; }
</style></head><body>
<table><tr><td style="text-align:{{ $al }}"><h1>{{ $co['name'] }}</h1><div class="muted">{{ $co['address'] }} · {{ $co['phone'] }} · {{ $co['email'] }}</div></td>
<td style="text-align:{{ $alr }}"><b>{{ __('quote.quotation') }}</b><br>{{ $file->ref }}<br>{{ d(now()) }}</td></tr></table>
<br>
<table class="box"><tr><td style="text-align:{{ $al }}"><b>{{ __('files.client') }}:</b> {{ $file->client->company }}<br>{{ $file->client->contact_person }}</td>
<td style="text-align:{{ $al }}"><b>{{ __('files.service') }}:</b> {{ __('services.'.$file->service) }}{{ $file->addons ? ' + '.collect($file->addons)->map(fn ($a) => __('services.'.$a))->join('، ') : '' }}<br>
<b>{{ __('files.route') }}:</b> {{ port_name($file->origin) }} → {{ port_name($file->destination) }} · {{ $file->incoterm }}</td></tr></table>
<br>
<table class="grid"><tr><th>{{ __('pricing.line') }}</th><th class="num">{{ __('common.amount') }}</th></tr>
@foreach ($file->quoteLines as $l)<tr><td>{{ $ar ? $l->description_ar : $l->description_en }}</td><td class="num">{{ number_format($l->amount, 2) }} {{ $l->currency }}</td></tr>@endforeach
</table>
<br>
<table class="box"><tr>
<td style="text-align:{{ $al }}"><b>{{ __('files.carrier') }}:</b> {{ $file->quote_carrier ?: '—' }}</td>
<td style="text-align:{{ $al }}"><b>{{ __('files.etd') }}:</b> {{ d($file->quote_etd) }}</td>
<td style="text-align:{{ $al }}"><b>{{ __('files.tt') }}:</b> {{ $file->quote_transit_days ? $file->quote_transit_days.' '.__('common.days') : '—' }}</td>
<td style="text-align:{{ $al }}"><b>{{ __('files.eta') }}:</b> {{ $file->quote_etd && $file->quote_transit_days ? d($file->quote_etd->copy()->addDays($file->quote_transit_days)) : '—' }}</td>
</tr></table>
@if ($file->chosenQuote)<p class="muted">{{ __('pricing.validity') }}: {{ d($file->chosenQuote->valid_until) }} · {{ __('pricing.free_days') }}: {{ $file->chosenQuote->free_days ?? '—' }}</p>@endif
<p class="muted">{{ __('quote.pdf_terms') }}</p>
<p>{{ __('quote.pdf_sign') }}<br><b>{{ $file->sales->name }}</b> · {{ $file->sales->email }}</p>
</body></html>
