@php
    $seeMoney = $u->can('viewMoney', $file);
    $canBill = $u->can('bill', $file);
    $canActual = $u->can('operate', $file) || $canBill;
    $currencies = array_merge(['USD'], App\Models\ExchangeRate::orderBy('currency')->pluck('currency')->all());
    $costTypes = ['freight', 'origin', 'destination', 'customs', 'trucking', 'storage', 'insurance', 'other'];
@endphp
<div class="stack">
<div class="grid g-main">
    <x-panel :title="__('invoice.title')" flush>
        @if ($u->can('viewInvoices', $file))
            @include('partials.invoice-table', ['invoices' => $file->invoices])
        @else <div class="locked p-3">{{ __('common.hidden_for_role') }}</div> @endif
        @php $missingCur = $file->quoteLines->pluck('currency')->unique()->diff($file->invoices->where('status', '!=', 'cancelled')->pluck('currency')); @endphp
        @if ($canBill && $missingCur->isNotEmpty())
            <form method="POST" action="{{ route('invoices.store', $file) }}" class="p-3 border-top">@csrf
                <div class="fw-semibold small mb-2">{{ __('invoice.create_from_file') }}</div>
                @foreach ($file->quoteLines->whereIn('currency', $missingCur) as $l)
                    <div class="split small py-1"><span>{{ rtl() ? $l->description_ar : $l->description_en }} · <span class="ltr">{{ money($l->amount, $l->currency) }}</span></span>
                        <span class="form-check form-switch m-0"><input type="hidden" name="vat[{{ $l->id }}]" value="0"><input class="form-check-input" type="checkbox" role="switch" name="vat[{{ $l->id }}]" value="1" id="vat-{{ $l->id }}" checked><label class="form-check-label" for="vat-{{ $l->id }}">{{ __('invoice.vat') }}</label></span></div>
                @endforeach
                <div class="d-flex gap-2 align-items-end mt-2"><div><label class="form-label" for="inv-due">{{ __('invoice.due_date') }}</label><input id="inv-due" type="date" name="due_date" class="form-control form-control-sm"></div>
                    <button class="btn btn-sm btn-primary">{{ __('invoice.create') }}</button></div>
                <div class="small-label mt-1">{{ __('invoice.one_per_currency') }}</div>
            </form>
        @endif
    </x-panel>
    <x-panel :title="__('invoice.actual_profit')">
        @if ($seeMoney)
            @php $invoiced = $file->invoices->where('status', '!=', 'cancelled')->sum(fn ($i) => App\Support\Money::toUsd($i->subtotal, $i->currency)); @endphp
            <dl class="dl"><dt>{{ __('invoice.invoiced') }}</dt><dd class="ltr">{{ money($invoiced) }}</dd><dt>{{ __('invoice.actual_costs') }}</dt><dd class="ltr">{{ money($actualCostUsd) }}</dd>
                <dt>{{ __('invoice.actual_profit') }}</dt><dd class="fw-bold ltr {{ ($actualProfit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ $actualProfit === null ? '—' : money($actualProfit) }}</dd>
                <dt>{{ __('quote.margin') }} ({{ __('pricing.estimated') }})</dt><dd class="ltr">{{ money($marginUsd) }}</dd></dl>
            <div class="small-label mt-2">{{ __('invoice.profit_note') }}</div>
        @else <div class="locked">{{ __('common.hidden_for_role') }}</div> @endif
    </x-panel>
</div>
@if ($canActual)
<x-panel :title="__('invoice.actual_cost_lines')">
    <form method="POST" action="{{ route('shipping.actual-costs', $file) }}" data-rows="actual">@csrf @method('PUT')
        <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('pricing.line') }}</th><th>{{ __('pricing.vendor') }}</th><th>{{ __('pricing.estimated') }}</th><th>{{ __('invoice.actual') }}</th><th>{{ __('invoice.vendor_due') }}</th><th>{{ __('common.status') }}</th></tr></thead><tbody>
        @foreach ($file->costLines as $i => $c)
            <tr><td>{{ __('costs.'.$c->type) }}@if($c->extra_fee) <x-pill kind="warn">{{ __('invoice.extra') }}</x-pill>@endif<input type="hidden" name="lines[{{ $i }}][id]" value="{{ $c->id }}"></td><td class="small">{{ $c->vendor?->name ?? '—' }}</td>
                <td class="ltr small">{{ money($c->estimated, $c->currency) }}</td>
                <td style="max-width:140px"><input type="number" step="0.01" min="0" name="lines[{{ $i }}][actual]" class="form-control form-control-sm num" value="{{ $c->actual }}" aria-label="{{ __('invoice.actual') }}"></td>
                <td><input type="date" name="lines[{{ $i }}][vendor_due_date]" class="form-control form-control-sm" value="{{ $c->vendor_due_date?->toDateString() }}" aria-label="{{ __('invoice.vendor_due') }}"></td>
                <td>@if($c->vendor_paid_at)<x-pill kind="ok">{{ __('invoice.paid') }}</x-pill>@elseif($c->vendor_due_date)<x-pill kind="{{ $c->vendor_due_date->isPast() ? 'bad' : 'warn' }}">{{ __('invoice.unpaid') }}</x-pill>@endif</td></tr>
        @endforeach
        </tbody></table></div>
        <div class="fw-semibold small mt-3">{{ __('invoice.add_extra') }}</div>
        @php $n = $file->costLines->count(); @endphp
        <div class="cost-row">
            <div><label class="form-label" for="ex-type">{{ __('pricing.line') }}</label><select id="ex-type" name="lines[{{ $n }}][type]" class="form-select form-select-sm">@foreach ($costTypes as $t)<option value="{{ $t }}">{{ __('costs.'.$t) }}</option>@endforeach</select></div>
            <div><label class="form-label" for="ex-vendor">{{ __('pricing.vendor') }}</label><select id="ex-vendor" name="lines[{{ $n }}][vendor_id]" class="form-select form-select-sm"><option value="">—</option>@foreach ($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
            <div><label class="form-label" for="ex-actual">{{ __('invoice.actual') }}</label><input id="ex-actual" type="number" step="0.01" min="0" name="lines[{{ $n }}][actual]" class="form-control form-control-sm num"></div>
            <div><label class="form-label" for="ex-cur">{{ __('common.currency') }}</label><select id="ex-cur" name="lines[{{ $n }}][currency]" class="form-select form-select-sm">@foreach ($currencies as $cur)<option>{{ $cur }}</option>@endforeach</select></div>
            <div><label class="form-label" for="ex-desc">{{ __('common.notes') }}</label><input id="ex-desc" name="lines[{{ $n }}][description]" class="form-control form-control-sm"></div>
        </div>
        <button class="btn btn-sm btn-primary mt-3">{{ __('common.save') }}</button>
    </form>
</x-panel>
@endif
</div>
