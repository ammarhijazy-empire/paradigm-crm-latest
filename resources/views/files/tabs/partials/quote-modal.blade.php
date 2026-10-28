<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-h" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ $action }}">@csrf @if($method === 'PUT') @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title" id="{{ $id }}-h">{{ $q ? __('pricing.check_confirm').' · '.$q->ref : __('pricing.add_quote') }}</h5></div>
    <div class="modal-body">
        @if ($q && $q->source === 'ai')<div class="ai-box small mb-3">{{ __('pricing.draft_ai') }} @if($q->inboundEmail)· {{ $q->inboundEmail->subject }}@endif</div>@endif
        <div class="grid g-3">
            @unless ($q)<div><label class="form-label" for="{{ $id }}-v">{{ __('pricing.vendor') }}</label><select id="{{ $id }}-v" name="vendor_id" class="form-select form-select-sm" required>@foreach ($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>@endunless
            <div><label class="form-label" for="{{ $id }}-c">{{ __('files.carrier') }}</label><input id="{{ $id }}-c" name="carrier" class="form-control form-control-sm" value="{{ $q?->carrier }}"></div>
            <div><label class="form-label" for="{{ $id }}-r">{{ __('pricing.routing') }}</label><input id="{{ $id }}-r" name="routing" class="form-control form-control-sm" value="{{ $q?->routing }}"></div>
            <div><label class="form-label" for="{{ $id }}-cur">{{ __('common.currency') }}</label><select id="{{ $id }}-cur" name="currency" class="form-select form-select-sm">@foreach ($currencies as $cur)<option @selected(($q?->currency ?? 'USD') === $cur)>{{ $cur }}</option>@endforeach</select></div>
            <div><label class="form-label" for="{{ $id }}-f">{{ __('pricing.freight') }}</label><input id="{{ $id }}-f" type="number" step="0.01" min="0" name="freight" class="form-control form-control-sm num" value="{{ $q?->freight }}" required></div>
            <div><label class="form-label" for="{{ $id }}-o">{{ __('pricing.origin_charges') }}</label><input id="{{ $id }}-o" type="number" step="0.01" min="0" name="origin_charges" class="form-control form-control-sm num" value="{{ $q?->origin_charges ?? 0 }}"></div>
            <div><label class="form-label" for="{{ $id }}-d">{{ __('pricing.destination_charges') }}</label><input id="{{ $id }}-d" type="number" step="0.01" min="0" name="destination_charges" class="form-control form-control-sm num" value="{{ $q?->destination_charges ?? 0 }}"></div>
            <div><label class="form-label" for="{{ $id }}-tt">{{ __('files.tt') }}</label><input id="{{ $id }}-tt" type="number" min="0" name="transit_days" class="form-control form-control-sm" value="{{ $q?->transit_days }}"></div>
            <div><label class="form-label" for="{{ $id }}-fd">{{ __('pricing.free_days') }}</label><input id="{{ $id }}-fd" type="number" min="0" name="free_days" class="form-control form-control-sm" value="{{ $q?->free_days }}"></div>
            <div><label class="form-label" for="{{ $id }}-vu">{{ __('pricing.validity') }}</label><input id="{{ $id }}-vu" type="date" name="valid_until" class="form-control form-control-sm" value="{{ $q?->valid_until?->toDateString() }}"></div>
            <div><label class="form-label" for="{{ $id }}-etd">{{ __('files.etd') }}</label><input id="{{ $id }}-etd" type="date" name="etd" class="form-control form-control-sm" value="{{ $q?->etd?->toDateString() }}"></div>
        </div>
        <div class="mt-3"><div class="form-label">{{ __('pricing.surcharges') }}</div>
            @foreach (array_pad($q?->surcharges ?? [], 5, ['name' => '', 'amount' => '']) as $si => $s)
                <div class="d-flex gap-2 mb-1"><input name="surcharges[{{ $si }}][name]" class="form-control form-control-sm" value="{{ $s['name'] }}" placeholder="THC, BAF…" aria-label="{{ __('pricing.surcharge_name') }}"><input type="number" step="0.01" min="0" name="surcharges[{{ $si }}][amount]" class="form-control form-control-sm num" style="max-width:140px" value="{{ $s['amount'] }}" aria-label="{{ __('common.amount') }}"></div>
            @endforeach
        </div>
        <label class="form-label mt-2" for="{{ $id }}-cond">{{ __('pricing.conditions') }}</label><textarea id="{{ $id }}-cond" name="conditions" class="form-control" rows="2">{{ $q?->conditions }}</textarea>
    </div>
    <div class="modal-footer">
        @if ($q)<button type="submit" form="{{ $id }}-reject" class="btn btn-outline-danger me-auto">{{ __('common.reject') }}</button>@endif
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary">{{ $q ? __('pricing.confirm_quote') : __('common.save') }}</button>
    </div>
</form>
@if ($q)<form id="{{ $id }}-reject" method="POST" action="{{ route('pricing.quotes.reject', $q) }}">@csrf @method('DELETE')</form>@endif
</div></div>
