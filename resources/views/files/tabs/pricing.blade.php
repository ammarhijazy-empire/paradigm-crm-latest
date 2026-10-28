@php
    $canPrice = $u->can('price', $file);
    $costTypes = ['freight', 'origin', 'destination', 'customs', 'trucking', 'storage', 'insurance', 'other'];
    $currencies = array_merge(['USD'], App\Models\ExchangeRate::orderBy('currency')->pluck('currency')->all());
    $pending = $file->costReviews->firstWhere('status', 'pending');
    $lastReview = $file->costReviews->first();
    $terms = $file->payTerms;
@endphp
<div class="stack">
    {{-- Vendor quotes, ranked --}}
    <x-panel :title="__('pricing.vq_title')" flush>
        @if ($canPrice)
            <x-slot:actions>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#m-rfq-vendors"><x-icon name="inbox" /> {{ __('pricing.send_rfq') }}</button>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#m-quote"><x-icon name="plus" /> {{ __('pricing.add_quote') }}</button>
            </x-slot:actions>
        @endif
        <div class="small-label px-3 pt-3">{{ __('pricing.rank_note') }}</div>
        @if ($ranked->isEmpty())
            <div class="empty">{{ __('common.none') }}</div>
        @else
        <div class="tbl-wrap"><table class="table align-middle mt-2">
            <thead><tr><th>#</th><th>{{ __('pricing.vendor') }}</th><th>{{ __('files.carrier') }}</th><th>{{ __('pricing.freight') }}</th><th>{{ __('pricing.surcharges') }}</th><th>{{ __('pricing.local') }}</th><th>{{ __('pricing.total_usd') }}</th><th>{{ __('files.tt') }}</th><th>{{ __('pricing.free_days') }}</th><th>{{ __('pricing.validity') }}</th><th>{{ __('pricing.notes') }}</th><th></th></tr></thead>
            <tbody>
            @foreach ($ranked as $q)
                <tr @class(['table-light' => $q->status === 'rejected'])>
                    <td><span class="rank-badge {{ $q->rank === 1 ? 'first' : '' }}">{{ $q->rank ?? '—' }}</span></td>
                    <td>{{ $q->vendor->name }}<div class="small-label mono">{{ $q->ref }}</div></td>
                    <td>{{ $q->carrier ?: '—' }}@if($q->routing)<div class="small-label">{{ $q->routing }}</div>@endif</td>
                    <td class="ltr">{{ money($q->freight, $q->currency) }}</td>
                    <td class="small">@foreach ($q->surcharges ?? [] as $s){{ $s['name'] }} {{ number_format($s['amount'], 2) }}<br>@endforeach</td>
                    <td class="ltr small">{{ money((float) $q->origin_charges + (float) $q->destination_charges, $q->currency) }}</td>
                    <td class="fw-semibold ltr">{{ money($q->total_usd) }}</td>
                    <td class="num">{{ $q->transit_days ?? '—' }}</td>
                    <td class="num">{{ $q->free_days ?? '—' }}</td>
                    <td class="num">@if($q->expired)<x-pill kind="bad">{{ d($q->valid_until) }}</x-pill>@else{{ d($q->valid_until) }}@endif</td>
                    <td class="small" style="min-width:180px">
                        @if($q->status === 'draft')<x-pill kind="info ai">{{ __('pricing.draft_ai') }}</x-pill><br>@endif
                        @if($q->status === 'rejected')<x-pill>{{ __('pricing.rejected') }}</x-pill><br>@endif
                        @foreach ($q->flags as $fl)<div class="text-warning">• {{ $fl }}</div>@endforeach
                        @if($q->conditions)<div class="muted">{{ Str::limit($q->conditions, 120) }}</div>@endif
                    </td>
                    <td class="text-nowrap">
                        @if ($q->is_chosen)<x-pill kind="ok">{{ __('pricing.chosen') }}</x-pill>
                        @elseif ($canPrice && $q->status === 'confirmed')
                            <form method="POST" action="{{ route('pricing.quotes.choose', [$file, $q]) }}" class="inline-form">@csrf<button class="btn btn-sm btn-outline-primary">{{ __('pricing.use_as_cost') }}</button></form>
                        @elseif ($canPrice && $q->status === 'draft')
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#m-confirm-{{ $q->id }}">{{ __('pricing.check_confirm') }}</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @endif
    </x-panel>

    <div class="grid g-main">
        {{-- Cost lines --}}
        <x-panel :title="__('pricing.cost_lines')">
            <x-slot:actions><x-pill :kind="['pending' => 'warn', 'review' => 'info', 'priced' => 'ok', 'won' => 'ok'][$file->pricing_status] ?? ''">{{ __('pricing.st_'.$file->pricing_status) }}</x-pill></x-slot:actions>
            @if ($canPrice)
                <form method="POST" action="{{ route('pricing.costs', $file) }}" data-rows="cost">@csrf @method('PUT')
                    <div data-rows-body>
                    @foreach ($file->costLines->where('extra_fee', false)->values() as $i => $c)
                        @include('files.tabs.partials.cost-row', ['i' => $i, 'c' => $c])
                    @endforeach
                    </div>
                    <template data-rows-template>@include('files.tabs.partials.cost-row', ['i' => '__I__', 'c' => null])</template>
                    <div class="d-flex gap-2 mt-2"><button type="button" class="btn btn-sm btn-light" data-rows-add>{{ __('rfq.add_row') }}</button><button class="btn btn-sm btn-primary ms-auto">{{ __('common.save') }}</button></div>
                </form>
            @else
                <div class="tbl-wrap"><table class="table"><thead><tr><th>{{ __('pricing.line') }}</th><th>{{ __('pricing.vendor') }}</th><th>{{ __('pricing.estimated') }}</th><th>{{ __('common.currency') }}</th></tr></thead><tbody>
                @forelse ($file->costLines as $c)<tr><td>{{ __('costs.'.$c->type) }}</td><td class="small">{{ $c->vendor?->name ?? '—' }}</td><td class="num">{{ number_format($c->estimated, 2) }}</td><td>{{ $c->currency }}</td></tr>
                @empty<tr><td colspan="4" class="empty">{{ __('common.none') }}</td></tr>@endforelse
                </tbody></table></div>
            @endif
            <div class="split mt-3 fw-semibold"><span>{{ __('pricing.total_cost_usd') }}</span><span class="ltr">{{ money($costUsd) }}</span></div>

            <hr>
            <form method="POST" action="{{ route('pricing.meta', $file) }}" class="grid g-3">@csrf @method('PUT')
                <div><label class="form-label" for="q-etd">{{ __('files.etd') }}</label><input id="q-etd" type="date" name="quote_etd" class="form-control form-control-sm" value="{{ $file->quote_etd?->toDateString() }}" @disabled(! $canPrice)></div>
                <div><label class="form-label" for="q-tt">{{ __('files.tt') }} ({{ __('common.days') }})</label><input id="q-tt" type="number" min="0" name="quote_transit_days" class="form-control form-control-sm" value="{{ $file->quote_transit_days }}" @disabled(! $canPrice)></div>
                <div><label class="form-label" for="q-carrier">{{ __('files.carrier') }}</label><input id="q-carrier" name="quote_carrier" class="form-control form-control-sm" value="{{ $file->quote_carrier }}" @disabled(! $canPrice)></div>
                @if ($file->quote_etd && $file->quote_transit_days)<div class="small-label">{{ __('files.eta') }} = {{ d($file->quote_etd->copy()->addDays($file->quote_transit_days)) }}</div>@endif
                @if ($canPrice)<div><button class="btn btn-sm btn-light">{{ __('common.save') }}</button></div>@endif
            </form>

            <div class="d-flex gap-2 flex-wrap mt-3">
                @if ($canPrice && in_array($file->pricing_status, ['pending', 'draft']))
                    <form method="POST" action="{{ route('pricing.submit-review', $file) }}">@csrf<button class="btn btn-sm btn-primary">{{ __('pricing.send_review') }}</button></form>
                @endif
                @if ($pending && $u->can('review', $file))
                    <form method="POST" action="{{ route('pricing.review', $file) }}" class="d-flex gap-2 flex-wrap align-items-end">@csrf
                        <input name="reason" class="form-control form-control-sm" style="max-width:260px" placeholder="{{ __('pricing.return_reason') }}" aria-label="{{ __('pricing.return_reason') }}">
                        <button name="decision" value="approve" class="btn btn-sm btn-primary">{{ __('common.approve') }}</button>
                        <button name="decision" value="return" class="btn btn-sm btn-light">{{ __('pricing.return') }}</button>
                    </form>
                @endif
            </div>
            @if ($lastReview && $lastReview->status === 'returned')<div class="gate-box bad mt-2">{{ __('pricing.returned_by', ['name' => $lastReview->reviewer?->name]) }}: {{ $lastReview->reason }}</div>@endif
        </x-panel>

        <div class="stack">
            <x-panel :title="__('pricing.pay_terms')">
                @if ($terms)
                    <div class="small mb-2">{{ __('pay.'.$terms->method) }}{{ $terms->credit_days ? ' · '.$terms->credit_days.' '.__('common.days') : '' }}</div>
                    @if ($terms->status === 'not_needed')
                        <div class="small-label">{{ __('pricing.pt_skip') }}</div>
                    @else
                        <div class="small-label mb-2">{{ __('pricing.pt_need') }}</div>
                        @if ($terms->status === 'approved')<x-pill kind="ok">{{ __('pricing.pt_approved') }}</x-pill>
                        @elseif ($terms->status === 'rejected')<x-pill kind="bad">{{ __('pricing.pt_rejected') }}</x-pill>
                        @else<x-pill kind="warn">{{ __('pricing.pt_pending') }}</x-pill>@endif
                        @if ($terms->note)<div class="small mt-2">{{ $terms->note }}</div>@endif
                        @if ($u->can('bill', $file) && $terms->status === 'pending')
                            <form method="POST" action="{{ route('pricing.pay-terms', $file) }}" class="mt-2 d-flex gap-2 flex-wrap">@csrf
                                <input name="note" class="form-control form-control-sm" placeholder="{{ __('common.notes') }}" aria-label="{{ __('common.notes') }}">
                                <button name="decision" value="approve" class="btn btn-sm btn-primary">{{ __('common.approve') }}</button>
                                <button name="decision" value="reject" class="btn btn-sm btn-light">{{ __('common.reject') }}</button>
                            </form>
                        @endif
                    @endif
                @else <div class="empty">—</div> @endif
            </x-panel>
            <x-panel :title="__('pricing.reprice_requests')">
                @forelse ($file->repriceRequests as $r)
                    <div class="py-2 border-bottom small">
                        <div class="split"><span><x-avatar :user="$r->requester" /> {{ $r->reason }}</span><x-pill :kind="$r->status === 'open' ? 'warn' : 'ok'">{{ __('pricing.rp_'.$r->status) }}</x-pill></div>
                        @if ($r->target_usd)<div class="small-label">{{ __('pricing.target') }}: {{ money($r->target_usd) }}</div>@endif
                        @if ($r->answer)<div class="mt-1">↳ {{ $r->answer }}</div>@endif
                        @if ($r->status === 'open' && $canPrice)
                            <form method="POST" action="{{ route('pricing.reprice.answer', $r) }}" class="d-flex gap-2 mt-2">@csrf
                                <input name="answer" class="form-control form-control-sm" required placeholder="{{ __('pricing.answer') }}" aria-label="{{ __('pricing.answer') }}">
                                <button class="btn btn-sm btn-light">{{ __('common.send') }}</button></form>
                        @endif
                    </div>
                @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
            </x-panel>
        </div>
    </div>
</div>

@if ($canPrice)
@push('modals')
<div class="modal fade" id="m-rfq-vendors" tabindex="-1" aria-labelledby="m-rfq-h" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('pricing.send-rfq', $file) }}">@csrf
    <div class="modal-header"><h5 class="modal-title" id="m-rfq-h">{{ __('pricing.send_rfq') }}</h5></div>
    <div class="modal-body">
        <div class="small-label mb-2">{{ __('pricing.subject') }}: <span class="mono">{{ $file->ref }}-OPS</span></div>
        @foreach ($vendors->whereIn('type', ['agent', 'line', 'air', 'cfs', 'truck', 'courier', 'broker', 'insurance']) as $v)
            <div class="form-check"><input class="form-check-input" type="checkbox" name="vendor_ids[]" value="{{ $v->id }}" id="rv-{{ $v->id }}" @disabled(! $v->email)>
                <label class="form-check-label" for="rv-{{ $v->id }}">{{ $v->name }} <span class="small-label">· {{ __('vendors.t_'.$v->type) }}{{ $v->email ? '' : ' · '.__('vendors.no_email') }}</span></label></div>
        @endforeach
        <label class="form-label mt-3" for="rfq-note">{{ __('common.notes') }}</label><textarea id="rfq-note" name="note" class="form-control" rows="2"></textarea>
        <div class="ai-box mt-3 small">{{ __('pricing.no_contact_sent') }}</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary">{{ __('common.send') }}</button></div>
</form></div></div>
@include('files.tabs.partials.quote-modal', ['id' => 'm-quote', 'action' => route('pricing.quotes.store', $file), 'q' => null, 'method' => 'POST'])
@foreach ($ranked->where('status', 'draft') as $q)
    @include('files.tabs.partials.quote-modal', ['id' => 'm-confirm-'.$q->id, 'action' => route('pricing.quotes.confirm', $q), 'q' => $q, 'method' => 'PUT'])
@endforeach
@endpush
@endif
