@extends('layouts.app')
@section('title', __('nav.settings'))
@section('content')
<x-page-head :title="__('settings.title')" />
@if (session('new_key'))<div class="alert-flash ok">{{ __('settings.key_once') }} <code class="mono user-select-all">{{ session('new_key') }}</code></div>@endif
<form method="POST" action="{{ route('settings.update') }}" class="stack mb-3">@csrf @method('PUT')
<div class="grid g-2">
    <x-panel :title="__('settings.rules')">
        <div class="grid g-2">
            <div><label class="form-label" for="s-margin">{{ __('settings.min_margin') }} (USD)</label><input id="s-margin" type="number" min="0" step="0.01" name="min_margin_usd" class="form-control num" value="{{ $minMargin }}"></div>
            <div><label class="form-label" for="s-vat">{{ __('settings.vat') }} %</label><input id="s-vat" type="number" min="0" max="100" step="0.01" name="vat_rate" class="form-control num" value="{{ $vat }}"></div>
            <div><label class="form-label" for="s-sla">{{ __('settings.lead_sla') }}</label><input id="s-sla" type="number" min="1" max="72" name="lead_sla_hours" class="form-control num" value="{{ $sla }}"></div>
            <div><label class="form-label" for="s-wait">{{ __('settings.stage_wait') }}</label><input id="s-wait" type="number" min="1" max="30" name="stage_wait_days" class="form-control num" value="{{ $wait }}"></div>
        </div>
    </x-panel>
    <x-panel :title="__('settings.ai')">
        <div class="small-label mb-2">{{ __('settings.ai_model') }}: <span class="mono">{{ config('crm.ai.model') }}</span> · {{ $aiConfigured ? __('settings.ai_connected') : __('settings.ai_not_configured') }}</div>
        @foreach (['whatsapp_rfq', 'vendor_email', 'doc_check'] as $k)
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="ai[{{ $k }}]" value="1" id="ai-{{ $k }}" @checked($ai[$k] ?? false)><label class="form-check-label" for="ai-{{ $k }}">{{ __('ai.f_'.$k) }}</label></div>
        @endforeach
        <label class="form-label mt-2" for="ai-limit">{{ __('settings.ai_limit') }} (USD)</label><input id="ai-limit" type="number" min="0" step="0.01" name="ai[monthly_limit_usd]" class="form-control num" style="max-width:180px" value="{{ $ai['monthly_limit_usd'] ?? 0 }}">
    </x-panel>
    <x-panel :title="__('settings.company')">
        <div class="grid g-2">@foreach (['name', 'tax_reg_no', 'commercial_reg_no', 'address', 'phone', 'email'] as $k)<div><label class="form-label" for="co-{{ $k }}">{{ __('settings.co_'.$k) }}</label><input id="co-{{ $k }}" name="company[{{ $k }}]" class="form-control form-control-sm" value="{{ $company[$k] ?? '' }}"></div>@endforeach</div>
    </x-panel>
    <x-panel :title="__('settings.banks')">
        @foreach ($banks as $cur => $b)
            <div class="fw-semibold small mt-2">{{ $cur }}</div>
            <div class="grid g-4">@foreach (['bank', 'account', 'iban', 'swift'] as $k)<input name="banks[{{ $cur }}][{{ $k }}]" class="form-control form-control-sm ltr" value="{{ $b[$k] ?? '' }}" placeholder="{{ __('settings.b_'.$k) }}" aria-label="{{ $cur }} {{ __('settings.b_'.$k) }}">@endforeach</div>
        @endforeach
    </x-panel>
</div>
<div><button class="btn btn-primary">{{ __('common.save') }}</button></div>
</form>
<div class="grid g-2">
    <x-panel :title="__('settings.rates')" flush>
        <x-slot:actions><form method="POST" action="{{ route('settings.rates.refresh') }}">@csrf<button class="btn btn-sm btn-light">{{ __('settings.refresh_now') }}</button></form></x-slot:actions>
        <form method="POST" action="{{ route('settings.rates') }}">@csrf @method('PUT')
            <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('common.currency') }}</th><th>1 USD =</th><th>{{ __('settings.locked') }}</th><th>{{ __('settings.updated') }}</th></tr></thead><tbody>
            @foreach ($rates as $r)
                <tr><td class="mono">{{ $r->currency }}</td><td><input type="number" step="0.000001" min="0" name="rates[{{ $r->currency }}][rate]" class="form-control form-control-sm num" style="max-width:150px" value="{{ $r->rate_per_usd }}" aria-label="{{ $r->currency }}"></td>
                    <td><input type="hidden" name="rates[{{ $r->currency }}][locked]" value="0"><input class="form-check-input" type="checkbox" name="rates[{{ $r->currency }}][locked]" value="1" @checked($r->locked) aria-label="{{ __('settings.locked') }}"></td><td class="small-label">{{ d($r->fetched_at ?? $r->updated_at, true) }} · {{ $r->source }}</td></tr>
            @endforeach
            </tbody></table></div>
            <div class="p-3 d-flex gap-2 align-items-center"><span class="small-label">{{ __('settings.rates_note') }}</span><button class="btn btn-sm btn-primary ms-auto">{{ __('common.save') }}</button></div>
        </form>
    </x-panel>
    <x-panel :title="__('settings.lead_keys')">
        @foreach ($leadKeys as $k)
            <div class="split py-2 border-bottom small"><span>{{ $k->name }} · {{ $k->last_used_at ? d($k->last_used_at, true) : __('settings.never_used') }}</span>
                @if ($k->active)<form method="POST" action="{{ route('settings.lead-key') }}">@csrf<input type="hidden" name="name" value="{{ $k->name }}"><input type="hidden" name="revoke" value="{{ $k->id }}"><button class="btn btn-sm btn-light">{{ __('settings.revoke') }}</button></form>@else<x-pill>{{ __('settings.revoked') }}</x-pill>@endif</div>
        @endforeach
        <form method="POST" action="{{ route('settings.lead-key') }}" class="d-flex gap-2 mt-3">@csrf<input name="name" class="form-control form-control-sm" placeholder="paradigmcargo.com" required aria-label="{{ __('settings.key_name') }}"><button class="btn btn-sm btn-light">{{ __('settings.new_key') }}</button></form>
        <div class="small-label mt-2">{{ __('settings.lead_api_note', ['header' => config('crm.lead_api_key_header')]) }} <span class="mono ltr">POST {{ url('/api/leads') }}</span></div>
    </x-panel>
</div>
@endsection
