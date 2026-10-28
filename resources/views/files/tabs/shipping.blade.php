@php $edit = $u->can('operate', $file); $b = $file->booking; @endphp
<div class="grid g-main">
    <x-panel :title="__('shipping.booking')">
        <form method="POST" action="{{ route('shipping.booking', $file) }}">@csrf @method('PUT')
            <fieldset @disabled(! $edit)>
            <div class="grid g-3">
                <div><label class="form-label" for="sh-carrier">{{ __('files.carrier') }}</label><input id="sh-carrier" name="carrier" class="form-control form-control-sm" value="{{ old('carrier', $file->carrier ?? $file->quote_carrier) }}"></div>
                <div><label class="form-label" for="sh-vendor">{{ __('pricing.vendor') }}</label><select id="sh-vendor" name="vendor_id" class="form-select form-select-sm"><option value="">—</option>@foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($b?->vendor_id === $v->id)>{{ $v->name }}</option>@endforeach</select></div>
                <div><label class="form-label" for="sh-bno">{{ __('shipping.booking_no') }}</label><input id="sh-bno" name="booking_no" class="form-control form-control-sm ltr" value="{{ $b?->booking_no }}"></div>
                <div><label class="form-label" for="sh-vessel">{{ __('shipping.vessel') }}</label><input id="sh-vessel" name="vessel_voyage" class="form-control form-control-sm ltr" value="{{ $b?->vessel_voyage }}"></div>
                <div><label class="form-label" for="sh-cutoff">{{ __('shipping.cutoff') }}</label><input id="sh-cutoff" type="datetime-local" name="cutoff_at" class="form-control form-control-sm" value="{{ $b?->cutoff_at?->format('Y-m-d\TH:i') }}"></div>
                <div><label class="form-label" for="sh-cfs">{{ __('shipping.cfs') }}</label><input id="sh-cfs" name="cfs_warehouse" class="form-control form-control-sm" value="{{ $b?->cfs_warehouse }}"></div>
                <div><label class="form-label" for="sh-bl">{{ __('files.bl') }}</label><input id="sh-bl" name="bl_no" class="form-control form-control-sm mono" value="{{ old('bl_no', $file->bl_no) }}"></div>
                <div><label class="form-label" for="sh-etd">{{ __('files.etd') }}</label><input id="sh-etd" type="date" name="etd" class="form-control form-control-sm" value="{{ $file->etd?->toDateString() }}"></div>
                <div><label class="form-label" for="sh-atd">{{ __('shipping.atd') }}</label><input id="sh-atd" type="date" name="atd" class="form-control form-control-sm" value="{{ $file->atd?->toDateString() }}"></div>
                <div><label class="form-label" for="sh-eta">{{ __('files.eta') }}</label><input id="sh-eta" type="date" name="eta" class="form-control form-control-sm" value="{{ $file->eta?->toDateString() }}"></div>
                <div><label class="form-label" for="sh-acid">{{ __('files.acid') }}</label><input id="sh-acid" name="acid_no" class="form-control form-control-sm mono" value="{{ old('acid_no', $file->acid_no) }}"></div>
            </div>
            <label class="form-label mt-2" for="sh-notes">{{ __('common.notes') }}</label><textarea id="sh-notes" name="notes" class="form-control" rows="2">{{ $b?->notes }}</textarea>
            @if ($file->quote_transit_days)<div class="small-label mt-2">{{ __('shipping.eta_rule', ['n' => $file->quote_transit_days]) }}</div>@endif
            @if ($edit)<button class="btn btn-sm btn-primary mt-3">{{ __('common.save') }}</button>@endif
            </fieldset>
        </form>
    </x-panel>
    <div class="stack">
        <x-panel :title="__('shipping.client_updates')">
            @forelse ($file->updates as $up)
                <div class="tl-item"><span class="tl-dot"></span><div style="min-width:0"><div class="small fw-semibold">{{ __('shipping.k_'.$up->kind) }}</div><div class="small text-pre">{{ Str::limit($up->body, 200) }}</div><div class="small-label">{{ d($up->created_at, true) }} · {{ $up->user->name }}</div></div></div>
            @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
            @if ($u->can('sendClientUpdate', $file))
                <form method="POST" action="{{ route('shipping.update', $file) }}" class="mt-3">@csrf
                    <div class="d-flex gap-2 mb-2"><select name="kind" class="form-select form-select-sm" aria-label="{{ __('shipping.kind') }}">@foreach (['prealert', 'eta_change', 'arrival', 'cleared', 'delivered', 'custom'] as $k)<option value="{{ $k }}">{{ __('shipping.k_'.$k) }}</option>@endforeach</select>
                        <select name="lang" class="form-select form-select-sm" style="max-width:120px" aria-label="{{ __('common.language') }}"><option value="ar">العربية</option><option value="en">English</option></select></div>
                    <textarea name="body" class="form-control" rows="3" required aria-label="{{ __('shipping.message') }}">{{ __('shipping.default_body', ['bl' => $file->bl_no ?: '—', 'eta' => d($file->eta), 'carrier' => $file->carrier ?: '—']) }}</textarea>
                    <div class="small-label mt-1">{{ __('shipping.to') }}: <span class="ltr">{{ $showContact ? $file->client->email : '—' }}</span></div>
                    <button class="btn btn-sm btn-light mt-2">{{ __('shipping.send_update') }}</button>
                </form>
            @endif
        </x-panel>
        <x-panel :title="__('shipping.events')">
            @forelse ($file->events as $e)
                <div class="tl-item"><span class="tl-dot"></span><div><div class="small">{{ $e->event }}{{ $e->location ? ' · '.$e->location : '' }}</div><div class="small-label">{{ d($e->happened_at, true) }}</div></div></div>
            @empty <div class="empty py-3">{{ __('common.none') }}</div> @endforelse
            @if ($edit)
                <form method="POST" action="{{ route('shipping.event', $file) }}" class="grid g-3 mt-2">@csrf
                    <input name="event" class="form-control form-control-sm" required placeholder="{{ __('shipping.event') }}" aria-label="{{ __('shipping.event') }}">
                    <input name="location" class="form-control form-control-sm" placeholder="{{ __('shipping.location') }}" aria-label="{{ __('shipping.location') }}">
                    <input name="happened_at" type="datetime-local" class="form-control form-control-sm" required aria-label="{{ __('common.date') }}">
                    <div><button class="btn btn-sm btn-light">{{ __('common.add') }}</button></div>
                </form>
            @endif
        </x-panel>
    </div>
</div>
