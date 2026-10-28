<div class="cost-row" data-row>
    <div><label class="form-label">{{ __('quote.section') }}</label><select name="lines[{{ $i }}][section]" class="form-select form-select-sm" data-f="section">@foreach ($sections as $s)<option value="{{ $s }}" @selected($l?->section === $s)>{{ __('sections.'.$s) }}</option>@endforeach</select></div>
    <div style="grid-column: span 2"><label class="form-label">{{ __('quote.desc_en') }}</label><input name="lines[{{ $i }}][description_en]" class="form-control form-control-sm" dir="ltr" value="{{ $l?->description_en }}" data-f="en"></div>
    <div style="grid-column: span 2"><label class="form-label">{{ __('quote.desc_ar') }}</label><input name="lines[{{ $i }}][description_ar]" class="form-control form-control-sm" dir="rtl" value="{{ $l?->description_ar }}" data-f="ar"></div>
    <div><label class="form-label">{{ __('common.amount') }}</label><input type="number" step="0.01" min="0" name="lines[{{ $i }}][amount]" class="form-control form-control-sm num" value="{{ $l?->amount }}" required data-f="amount"></div>
    <div><label class="form-label">{{ __('common.currency') }}</label><select name="lines[{{ $i }}][currency]" class="form-select form-select-sm" data-f="currency">@foreach ($currencies as $cur)<option @selected(($l?->currency ?? 'USD') === $cur)>{{ $cur }}</option>@endforeach</select></div>
    <div><button type="button" class="btn btn-sm btn-light w-100" data-rows-remove>{{ __('common.remove') }}</button></div>
</div>
