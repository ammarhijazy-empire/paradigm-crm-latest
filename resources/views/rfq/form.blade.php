@extends('layouts.app')
@section('title', __('rfq.title'))
@section('content')
@php
    $f = $file;
    $v = fn ($k, $def = null) => old($k, data_get($f ? ['client_id' => $f->client_id, 'direction' => $f->direction, 'service' => $f->service, 'addons' => $f->addons, 'origin' => $f->origin, 'destination' => $f->destination, 'incoterm' => $f->incoterm, 'pickup_address' => $f->pickup_address, 'delivery_address' => $f->delivery_address, 'inq' => $f->inq] : [], $k, $def));
    $rows = old('inq.rows', $f->inq['rows'] ?? []);
    $i18n = collect(['p_client', 'p_service', 'p_ins_alone', 'p_rows', 'p_row_incomplete', 'p_origin', 'p_dest', 'p_incoterm', 'p_exw', 'p_delivery', 'p_courier_addr', 'p_ready', 'p_ready_past', 'p_haz', 'p_temp', 'p_oog', 'p_free', 'p_freight', 'p_pay', 'p_credit', 'ok', 'confirm_ai'])->mapWithKeys(fn ($k) => [$k => __('rfq.'.$k)])->all()
        + ['remove' => __('common.remove'), 'type' => __('rfq.cont_type'), 'count' => __('rfq.count'), 'pieces' => __('rfq.pieces'), 'l' => __('rfq.len'), 'w' => __('rfq.wid'), 'h' => __('rfq.hei'), 'kg' => __('rfq.weight'), 'tonnes' => __('rfq.tonnes'), 'truck' => __('rfq.truck'), 'chargeable' => __('rfq.chargeable'), 'cbm' => __('rfq.cbm'),
           'reading' => __('ai.reading'), 'failed' => __('ai.failed'), 'filled' => __('ai.filled'), 'ask' => __('ai.still_to_ask'), 'transcript' => __('ai.transcript')];
    $preClient = $v('client_id', $preset['client_id'] ?? null);
@endphp
<x-page-head :title="$f ? __('rfq.edit_title', ['ref' => $f->ref]) : __('rfq.title')" :sub="__('rfq.sub')">
    @if (! $f && (App\Support\Settings::get('ai')['whatsapp_rfq'] ?? false))
        <x-slot:actions><button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#m-wa"><x-icon name="wa" /> {{ __('ai.fill_wa') }}</button></x-slot:actions>
    @endif
</x-page-head>
<form method="POST" action="{{ $f ? route('rfq.update', $f) : route('rfq.store') }}" enctype="multipart/form-data" id="rfq-form" novalidate
      data-i18n="{{ json_encode($i18n, JSON_UNESCAPED_UNICODE) }}" data-rows="{{ json_encode(array_values($rows)) }}" data-today="{{ App\Support\Dates::today()->toDateString() }}">
    @csrf @if ($f) @method('PUT') @endif
    <input type="hidden" name="ai_run_id" id="ai_run_id" value="{{ old('ai_run_id') }}">
<div class="grid g-main">
    <div class="panel">
        <div class="p-3 pb-0" id="ai-box-wrap" hidden><div class="ai-box mb-3" id="ai-box">
            <h4 id="ai-box-title"></h4><div class="small" id="ai-box-body"></div>
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="ai_checked" value="1" id="ai_checked"><label class="form-check-label" for="ai_checked">{{ __('ai.confirm_fields') }}</label></div>
        </div></div>
        <div class="form-section"><h3><span class="idx">01</span>{{ __('rfq.client') }}</h3><div class="grid g-2">
            <div><label class="form-label" for="client_id">{{ __('rfq.client') }}</label>
                <select id="client_id" name="client_id" class="form-select form-select-sm" required @disabled((bool) $f)>
                    <option value="">—</option>@foreach ($clients as $c)<option value="{{ $c->id }}" @selected($preClient == $c->id)>{{ $c->company }}</option>@endforeach
                </select>
                @if ($f)<input type="hidden" name="client_id" value="{{ $f->client_id }}">@endif
                @if (! $f)<a class="small" href="{{ route('clients.create') }}">+ {{ __('clients.new') }}</a>@endif
            </div>
            <div><span class="form-label d-block">{{ __('rfq.direction') }}</span><div class="btn-group btn-group-sm" role="group">
                @foreach (['import', 'export'] as $dd)<input type="radio" class="btn-check" name="direction" id="dir-{{ $dd }}" value="{{ $dd }}" @checked($v('direction', 'import') === $dd)><label class="btn btn-outline-primary" for="dir-{{ $dd }}">{{ __('rfq.'.$dd) }}</label>@endforeach
            </div></div>
        </div></div>
        <div class="form-section"><h3><span class="idx">02</span>{{ __('rfq.services') }}</h3>
            <div class="small-label mb-2">{{ __('rfq.main_service') }}</div>
            <div class="svc-grid mb-3" data-ai="service">@foreach (App\Models\ShipmentFile::MAIN_SERVICES as $s)<input type="radio" class="btn-check" name="service" id="svc-{{ $s }}" value="{{ $s }}" @checked($v('service') === $s)><label class="btn btn-sm btn-outline-primary" for="svc-{{ $s }}">{{ __('services.'.$s) }}</label>@endforeach</div>
            <div class="small-label mb-2">{{ __('rfq.addons') }}</div>
            <div class="svc-grid" data-ai="addons">@foreach (App\Models\ShipmentFile::ADDONS as $s)<input type="checkbox" class="btn-check" name="addons[]" id="add-{{ $s }}" value="{{ $s }}" @checked(in_array($s, $v('addons', []) ?? []))><label class="btn btn-sm btn-outline-primary" for="add-{{ $s }}">{{ __('services.'.$s) }}</label>@endforeach</div>
        </div>
        <div class="form-section"><h3><span class="idx">03</span>{{ __('rfq.volume') }}</h3>
            <div id="rows" data-ai="rows"></div>
            <button type="button" class="btn btn-sm btn-light" id="row-add">{{ __('rfq.add_row') }}</button>
            <div class="small-label mt-2" id="rows-hint">{{ __('rfq.p_service') }}</div>
        </div>
        <div class="form-section"><h3><span class="idx">04</span>{{ __('rfq.places') }}</h3><div class="grid g-3">
            @foreach (['origin' => 'rfq.origin', 'destination' => 'rfq.destination'] as $k => $lbl)
                <div><label class="form-label" for="{{ $k }}">{{ __($lbl) }}</label><select id="{{ $k }}" name="{{ $k }}" class="form-select form-select-sm" data-ports data-ai="{{ $k }}"><option value="">—</option>
                    @foreach ($ports->groupBy('country') as $country => $list)<optgroup label="{{ $country }}">@foreach ($list as $p)<option value="{{ $p->code }}" data-kind="{{ $p->kind }}" @selected($v($k) === $p->code)>{{ rtl() ? $p->name_ar : $p->name_en }} ({{ $p->code }})</option>@endforeach</optgroup>@endforeach
                </select></div>
            @endforeach
            <div><label class="form-label" for="incoterm">{{ __('rfq.incoterm') }}</label><select id="incoterm" name="incoterm" class="form-select form-select-sm" data-ai="incoterm"><option value="">—</option>@foreach (App\Domain\InquiryValidator::INCOTERMS_SEA as $i)<option @selected($v('incoterm') === $i) data-other="{{ in_array($i, App\Domain\InquiryValidator::INCOTERMS_OTHER) ? 1 : 0 }}">{{ $i }}</option>@endforeach</select></div>
            <div data-show="pickup"><label class="form-label" for="pickup_address">{{ __('rfq.pickup_address') }}</label><input id="pickup_address" name="pickup_address" class="form-control form-control-sm" value="{{ $v('pickup_address') }}" data-ai="pickup_address"></div>
            <div data-show="delivery"><label class="form-label" for="delivery_address">{{ __('rfq.delivery_address') }}</label><input id="delivery_address" name="delivery_address" class="form-control form-control-sm" value="{{ $v('delivery_address') }}" data-ai="delivery_address"></div>
        </div></div>
        <div class="form-section"><h3><span class="idx">05</span>{{ __('rfq.cargo') }}</h3><div class="grid g-2">
            <div><label class="form-label" for="inq-commodity">{{ __('rfq.commodity') }}</label><input id="inq-commodity" name="inq[commodity]" class="form-control form-control-sm" value="{{ $v('inq.commodity') }}" data-ai="inq.commodity"></div>
            <div><label class="form-label" for="inq-cargo">{{ __('rfq.cargo_type') }}</label><select id="inq-cargo" name="inq[cargo]" class="form-select form-select-sm" data-ai="inq.cargo">@foreach (['general', 'hazardous', 'reefer', 'fragile', 'liquid', 'oog'] as $c)<option value="{{ $c }}" @selected($v('inq.cargo', 'general') === $c)>{{ __('cargo.'.$c) }}</option>@endforeach</select></div>
        </div>
            <div class="grid g-4 mt-2" data-show="haz">
                <div><label class="form-label" for="inq-un">{{ __('rfq.un') }}</label><input id="inq-un" name="inq[un]" class="form-control form-control-sm" value="{{ $v('inq.un') }}"></div>
                <div><label class="form-label" for="inq-imo">{{ __('rfq.imo') }}</label><input id="inq-imo" name="inq[imo]" class="form-control form-control-sm" value="{{ $v('inq.imo') }}"></div>
                <div><label class="form-label" for="inq-pg">{{ __('rfq.pg') }}</label><select id="inq-pg" name="inq[pg]" class="form-select form-select-sm"><option value="">—</option>@foreach (['I', 'II', 'III'] as $pg)<option @selected($v('inq.pg') === $pg)>{{ $pg }}</option>@endforeach</select></div>
                <div><label class="form-label" for="msds">MSDS @if(!empty($f?->inq['msds']))<span class="small-label">✓</span>@endif</label><input id="msds" type="file" name="msds" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png"><input type="hidden" name="inq[msds_doc]" value="{{ $f->inq['msds'] ?? '' }}" id="msds-doc"></div>
            </div>
            <div class="mt-2" style="max-width:220px" data-show="reefer"><label class="form-label" for="inq-temp">{{ __('rfq.temp') }}</label><input id="inq-temp" type="number" step="0.5" name="inq[temp]" class="form-control form-control-sm" value="{{ $v('inq.temp') }}"></div>
            <div class="grid g-3 mt-2" data-show="oog">
                @foreach (['l' => 'rfq.len', 'w' => 'rfq.wid', 'h' => 'rfq.hei'] as $k => $lbl)<div><label class="form-label" for="oog-{{ $k }}">{{ __($lbl) }}</label><input id="oog-{{ $k }}" type="number" min="0" name="inq[oog_{{ $k }}]" class="form-control form-control-sm num" value="{{ $v('inq.oog_'.$k) }}"></div>@endforeach
            </div>
        </div>
        <div class="form-section"><h3><span class="idx">06</span>{{ __('rfq.dates_payment') }}</h3><div class="grid g-3">
            <div><label class="form-label" for="inq-ready">{{ __('rfq.ready') }}</label><input id="inq-ready" type="date" name="inq[ready]" class="form-control form-control-sm" value="{{ $v('inq.ready') }}" data-ai="inq.ready"></div>
            <div><label class="form-label" for="inq-price_by">{{ __('rfq.price_by') }}</label><input id="inq-price_by" type="date" name="inq[price_by]" class="form-control form-control-sm" value="{{ $v('inq.price_by') }}" data-ai="inq.price_by"></div>
            <div data-show="free"><label class="form-label" for="inq-free_days">{{ __('rfq.free_days') }}</label><input id="inq-free_days" type="number" min="0" name="inq[free_days]" class="form-control form-control-sm" value="{{ $v('inq.free_days') }}" data-ai="inq.free_days"></div>
            <div><label class="form-label" for="inq-freight_terms">{{ __('rfq.freight_terms') }}</label><select id="inq-freight_terms" name="inq[freight_terms]" class="form-select form-select-sm" data-ai="inq.freight_terms"><option value="">—</option>@foreach (['prepaid', 'collect'] as $x)<option value="{{ $x }}" @selected($v('inq.freight_terms') === $x)>{{ __('rfq.'.$x) }}</option>@endforeach</select></div>
            <div><label class="form-label" for="inq-pay">{{ __('rfq.pay') }}</label><select id="inq-pay" name="inq[pay]" class="form-select form-select-sm" data-ai="inq.pay"><option value="">—</option>@foreach (['cash_in_advance', 'against_documents', 'on_arrival', 'credit'] as $x)<option value="{{ $x }}" @selected($v('inq.pay') === $x)>{{ __('pay.'.$x) }}</option>@endforeach</select></div>
            <div data-show="credit"><label class="form-label" for="inq-credit_days">{{ __('rfq.credit_days') }}</label><input id="inq-credit_days" type="number" min="1" name="inq[credit_days]" class="form-control form-control-sm" value="{{ $v('inq.credit_days') }}" data-ai="inq.credit_days"></div>
        </div>
            <label class="form-label mt-2" for="inq-notes">{{ __('common.notes') }}</label><textarea id="inq-notes" name="inq[notes]" class="form-control" rows="2" data-ai="inq.notes">{{ $v('inq.notes') }}</textarea>
            @unless ($f)<label class="form-label mt-2" for="attachments">{{ __('rfq.attachments') }}</label><input id="attachments" type="file" name="attachments[]" multiple class="form-control form-control-sm">@endunless
        </div>
    </div>
    <div><div class="panel problems"><div class="panel-h"><h2>{{ __('rfq.check') }}</h2><span class="pill" id="prob-count">—</span></div><div class="panel-b">
        <div id="problems"></div>
        <div class="d-grid gap-2 mt-3">
            @if (! $f || $f->pricing_status === 'draft')
                <button class="btn btn-primary" name="action" value="send" id="btn-send">{{ __('rfq.send') }}</button>
            @endif
            <button class="btn btn-light" name="action" value="{{ $f && $f->pricing_status !== 'draft' ? 'send' : 'draft' }}" formnovalidate>{{ $f && $f->pricing_status !== 'draft' ? __('common.save') : __('rfq.save_draft') }}</button>
        </div>
        <div class="small-label mt-2">{{ __('rfq.server_check') }}</div>
    </div></div></div>
</div>
</form>

@push('modals')
<div class="modal fade" id="m-wa" tabindex="-1" aria-labelledby="m-wa-h" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><form class="modal-content" id="wa-form" action="{{ route('ai.whatsapp') }}" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title" id="m-wa-h"><x-icon name="wa" /> {{ __('ai.fill_wa') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button></div>
    <div class="modal-body">
        <p class="small muted">{{ __('ai.wa_hint') }}</p>
        <label class="form-label" for="wa-text">{{ __('ai.chat_text') }}</label><textarea id="wa-text" name="text" class="form-control mb-3" rows="7" dir="auto"></textarea>
        <label class="form-label" for="wa-files">{{ __('ai.chat_files') }}</label><input id="wa-files" type="file" name="files[]" multiple class="form-control" accept=".txt,.zip,.opus,.ogg,.oga,.m4a,.mp3,.aac,.wav,.mp4">
        <div class="ai-box mt-3 small">{{ __('ai.suggests') }}</div>
        <div class="small mt-2" id="wa-status" role="status"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary" id="wa-run"><x-icon name="spark" /> {{ __('ai.read') }}</button></div>
</form></div></div>
@endpush
@endsection
