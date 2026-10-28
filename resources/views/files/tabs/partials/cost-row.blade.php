<div class="cost-row" data-row>
    <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $c?->id }}">
    <div><label class="form-label">{{ __('pricing.line') }}</label><select name="lines[{{ $i }}][type]" class="form-select form-select-sm">@foreach ($costTypes as $t)<option value="{{ $t }}" @selected($c?->type === $t)>{{ __('costs.'.$t) }}</option>@endforeach</select></div>
    <div><label class="form-label">{{ __('pricing.vendor') }}</label><select name="lines[{{ $i }}][vendor_id]" class="form-select form-select-sm"><option value="">—</option>@foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($c?->vendor_id === $v->id)>{{ $v->name }}</option>@endforeach</select></div>
    <div><label class="form-label">{{ __('pricing.estimated') }}</label><input type="number" step="0.01" min="0" name="lines[{{ $i }}][estimated]" class="form-control form-control-sm num" value="{{ $c?->estimated }}" required></div>
    <div><label class="form-label">{{ __('common.currency') }}</label><select name="lines[{{ $i }}][currency]" class="form-select form-select-sm">@foreach ($currencies as $cur)<option @selected(($c?->currency ?? 'USD') === $cur)>{{ $cur }}</option>@endforeach</select></div>
    <div><label class="form-label">{{ __('common.notes') }}</label><input name="lines[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $c?->description }}"></div>
    <div class="form-check pb-1"><input class="form-check-input" type="checkbox" value="1" name="lines[{{ $i }}][_delete]" id="cd-{{ $i }}"><label class="form-check-label small" for="cd-{{ $i }}">{{ __('common.remove') }}</label></div>
</div>
