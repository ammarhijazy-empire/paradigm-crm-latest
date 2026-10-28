@php
    $canQuote = $u->can('quote', $file) && $file->stage <= 2;
    $currencies = array_merge(['USD'], App\Models\ExchangeRate::orderBy('currency')->pluck('currency')->all());
    $sections = ['freight', 'origin', 'destination', 'clearance', 'trucking', 'insurance', 'other'];
    $seeCost = $u->isManagement() || $u->hasAnyRole(['sales', 'pricing', 'pricing_review', 'accounts']);
    $below = $marginUsd < $minMargin;
    $fromCosts = json_encode($file->costLines->map(fn ($c) => [
        'section' => in_array($c->type, $sections) ? $c->type : ($c->type === 'customs' ? 'clearance' : 'other'),
        'currency' => $c->currency, 'amount' => (float) $c->estimated,
        'en' => __('costs.'.$c->type, [], 'en'), 'ar' => __('costs.'.$c->type, [], 'ar'),
    ])->values(), JSON_UNESCAPED_UNICODE);
@endphp
<div class="grid g-main">
    <div class="stack">
        <x-panel :title="__('quote.selling')">
            @if ($file->quoteLines->isNotEmpty() && ($canQuote || $u->can('viewClientContact', $file)))
                <x-slot:actions>
                    <a class="btn btn-sm btn-light" href="{{ route('quote.pdf', $file) }}?lang=en" target="_blank" rel="noopener"><x-icon name="pdf" /> {{ __('quote.pdf_en') }}</a>
                    <a class="btn btn-sm btn-light" href="{{ route('quote.pdf', $file) }}?lang=ar" target="_blank" rel="noopener"><x-icon name="pdf" /> {{ __('quote.pdf_ar') }}</a>
                </x-slot:actions>
            @endif
            @if ($canQuote)
                @if (! in_array($file->pricing_status, ['priced', 'quoted', 'won']) && ! $u->isManagement())
                    <div class="gate-box bad mb-3">{{ __('quote.cost_not_ready') }}</div>
                @endif
                <form method="POST" action="{{ route('quote.lines', $file) }}" data-rows="quote">@csrf @method('PUT')
                    <div data-rows-body>
                        @foreach ($file->quoteLines as $i => $l)@include('files.tabs.partials.quote-row', ['i' => $i, 'l' => $l])@endforeach
                    </div>
                    <template data-rows-template>@include('files.tabs.partials.quote-row', ['i' => '__I__', 'l' => null])</template>
                    <div class="d-flex gap-2 mt-2"><button type="button" class="btn btn-sm btn-light" data-rows-add>{{ __('rfq.add_row') }}</button>
                        @if ($file->quoteLines->isEmpty() && $file->costLines->isNotEmpty())<button type="button" class="btn btn-sm btn-light" data-fill-from-costs="{{ $fromCosts }}">{{ __('quote.from_costs') }}</button>@endif
                        <button class="btn btn-sm btn-primary ms-auto">{{ __('common.save') }}</button></div>
                </form>
            @else
                <div class="tbl-wrap"><table class="table"><thead><tr><th>{{ __('pricing.line') }}</th><th>{{ __('common.amount') }}</th><th class="text-end">USD</th></tr></thead><tbody>
                @forelse ($file->quoteLines as $l)<tr><td>{{ rtl() ? $l->description_ar : $l->description_en }}<div class="small-label">{{ __('sections.'.$l->section) }}</div></td><td class="ltr">{{ money($l->amount, $l->currency) }}</td><td class="text-end ltr">{{ money(App\Support\Money::toUsd($l->amount, $l->currency)) }}</td></tr>
                @empty<tr><td colspan="3" class="empty">{{ __('common.none') }}</td></tr>@endforelse
                </tbody></table></div>
            @endif
        </x-panel>
        <x-panel :title="__('quote.rounds')" flush>
            <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('quote.round') }}</th><th>{{ __('quote.our_price') }}</th><th>{{ __('quote.counter') }}</th><th>{{ __('quote.outcome') }}</th><th>{{ __('common.notes') }}</th></tr></thead><tbody>
            @forelse ($file->quoteRounds as $r)
                <tr><td class="num">{{ $r->round }}</td><td class="ltr">{{ money($r->our_price_usd) }}</td>
                    @if ($canQuote)
                        <td colspan="3"><form method="POST" action="{{ route('quote.rounds.update', $r) }}" class="d-flex gap-2 flex-wrap">@csrf @method('PUT')
                            <input type="number" step="0.01" min="0" name="counter_offer_usd" class="form-control form-control-sm num" style="max-width:130px" value="{{ $r->counter_offer_usd }}" aria-label="{{ __('quote.counter') }}">
                            <select name="outcome" class="form-select form-select-sm" style="max-width:150px" aria-label="{{ __('quote.outcome') }}">@foreach (['pending', 'countered', 'accepted', 'rejected'] as $o)<option value="{{ $o }}" @selected($r->outcome === $o)>{{ __('quote.o_'.$o) }}</option>@endforeach</select>
                            <input name="notes" class="form-control form-control-sm" value="{{ $r->notes }}" aria-label="{{ __('common.notes') }}">
                            <button class="btn btn-sm btn-light">{{ __('common.save') }}</button></form></td>
                    @else
                        <td class="ltr">{{ $r->counter_offer_usd ? money($r->counter_offer_usd) : '—' }}</td><td>{{ __('quote.o_'.$r->outcome) }}</td><td class="small">{{ $r->notes }}</td>
                    @endif
                </tr>
            @empty <tr><td colspan="5" class="empty">{{ __('common.none') }}</td></tr> @endforelse
            </tbody></table></div>
            <div class="p-3 border-top d-flex gap-2 flex-wrap align-items-center">
                <span class="small-label">{{ __('quote.max_rounds', ['n' => App\Domain\QuotationService::MAX_ROUNDS]) }}</span>
                @if ($canQuote && $file->quoteRounds->count() < App\Domain\QuotationService::MAX_ROUNDS && $file->quoteRounds->isNotEmpty())
                    <form method="POST" action="{{ route('quote.rounds.store', $file) }}" class="ms-auto">@csrf<input type="hidden" name="outcome" value="pending"><button class="btn btn-sm btn-light">{{ __('quote.new_round') }}</button></form>
                @endif
                @if ($canQuote)<button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#m-reprice">{{ __('quote.ask_lower') }}</button>@endif
            </div>
        </x-panel>
    </div>
    <x-panel :title="__('quote.margin')">
        @if ($seeCost)
            <dl class="dl mb-3"><dt>{{ __('quote.selling') }}</dt><dd class="fw-semibold ltr">{{ money($sellUsd) }}</dd><dt>{{ __('quote.cost') }}</dt><dd class="ltr">{{ money($costUsd) }}</dd>
                <dt>{{ __('quote.margin') }}</dt><dd class="fw-bold ltr {{ $below ? 'text-danger' : 'text-success' }}">{{ money($marginUsd) }}</dd></dl>
            <div class="small-label mb-2">{{ __('quote.min_margin', ['amt' => money($minMargin)]) }}</div>
            @if ($file->quoteLines->isNotEmpty())
                @if ($below && ! $file->margin_approved_at)
                    <div class="gate-box bad">{{ __('quote.margin_low') }}</div>
                    @if ($u->isManagement())<form method="POST" action="{{ route('quote.approve-margin', $file) }}" class="mt-2">@csrf<button class="btn btn-sm btn-primary">{{ __('quote.approve_margin') }}</button></form>@endif
                @elseif ($below)
                    <div class="gate-box ok">{{ __('quote.margin_approved_on', ['date' => d($file->margin_approved_at)]) }}</div>
                @else
                    <div class="gate-box ok">{{ __('quote.margin_ok') }}</div>
                @endif
            @endif
        @else
            <div class="locked">{{ __('common.hidden_for_role') }}</div>
        @endif
        @if ($canQuote && $file->quoteLines->isNotEmpty())
            <hr>
            @if ($sendProblems)<div class="gate-box bad mb-2"><ul class="mt-0">@foreach ($sendProblems as $p)<li>{{ $p }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('quote.sent', $file) }}" class="d-flex gap-2 align-items-end flex-wrap">@csrf
                <div><label class="form-label" for="qs-lang">{{ __('quote.sent_in') }}</label><select id="qs-lang" name="lang" class="form-select form-select-sm"><option value="ar">العربية</option><option value="en">English</option></select></div>
                <button class="btn btn-sm btn-primary" @disabled($sendProblems)>{{ __('quote.mark_sent') }}</button>
            </form>
        @endif
    </x-panel>
</div>
@if ($canQuote)
@push('modals')
<div class="modal fade" id="m-reprice" tabindex="-1" aria-labelledby="m-rp-h" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('quote.reprice', $file) }}">@csrf
    <div class="modal-header"><h5 class="modal-title" id="m-rp-h">{{ __('quote.ask_lower') }}</h5></div>
    <div class="modal-body"><label class="form-label" for="rp-reason">{{ __('quote.reprice_reason') }}</label><textarea id="rp-reason" name="reason" class="form-control" rows="3" required></textarea>
        <label class="form-label mt-2" for="rp-target">{{ __('pricing.target') }} (USD)</label><input id="rp-target" name="target_usd" type="number" step="0.01" min="0" class="form-control num"></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary">{{ __('common.send') }}</button></div>
</form></div></div>
@endpush
@endif
