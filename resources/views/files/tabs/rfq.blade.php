@php $q = $file->inq ?? []; $probs = App\Domain\InquiryValidator::problems(['client_id' => $file->client_id, 'service' => $file->service, 'addons' => $file->addons ?? [], 'origin' => $file->origin, 'destination' => $file->destination, 'incoterm' => $file->incoterm, 'pickup_address' => $file->pickup_address, 'delivery_address' => $file->delivery_address, 'inq' => $q, '_skip_past_check' => $file->stage > 1]); @endphp
<div class="grid g-main">
    <x-panel :title="__('files.t_rfq')">
        <x-slot:actions>@can('updateRfq', $file)<a class="btn btn-sm btn-light" href="{{ route('rfq.edit', $file) }}">{{ __('common.edit') }}</a>@endcan</x-slot:actions>
        <dl class="dl">
            <dt>{{ __('rfq.direction') }}</dt><dd>{{ __('rfq.'.$file->direction) }}</dd>
            <dt>{{ __('rfq.main_service') }}</dt><dd>{{ __('services.'.$file->service) }}</dd>
            <dt>{{ __('rfq.addons') }}</dt><dd>{{ collect($file->addons)->map(fn ($a) => __('services.'.$a))->join('، ') ?: '—' }}</dd>
            <dt>{{ __('rfq.volume') }}</dt><dd class="ltr">@include('partials.volume', ['f' => $file])</dd>
            <dt>{{ __('rfq.origin') }}</dt><dd>{{ port_name($file->origin) }}@if($file->pickup_address)<div class="small-label">{{ __('rfq.pickup_address') }}: {{ $file->pickup_address }}</div>@endif</dd>
            <dt>{{ __('rfq.destination') }}</dt><dd>{{ port_name($file->destination) }}@if($file->delivery_address)<div class="small-label">{{ __('rfq.delivery_address') }}: {{ $file->delivery_address }}</div>@endif</dd>
            <dt>{{ __('rfq.incoterm') }}</dt><dd>{{ $file->incoterm ?: '—' }}</dd>
            <dt>{{ __('rfq.cargo_type') }}</dt><dd>{{ __('cargo.'.($q['cargo'] ?? 'general')) }}
                @if(($q['cargo'] ?? '') === 'hazardous')<div class="small-label">UN {{ $q['un'] ?? '—' }} · IMO {{ $q['imo'] ?? '—' }} · PG {{ $q['pg'] ?? '—' }}</div>@endif
                @if(($q['cargo'] ?? '') === 'reefer')<div class="small-label">{{ $q['temp'] ?? '—' }} °C</div>@endif
                @if(($q['cargo'] ?? '') === 'oog')<div class="small-label ltr">{{ $q['oog_l'] ?? '—' }}×{{ $q['oog_w'] ?? '—' }}×{{ $q['oog_h'] ?? '—' }} cm</div>@endif
            </dd>
            <dt>{{ __('rfq.commodity') }}</dt><dd>{{ $q['commodity'] ?? '—' }}</dd>
            <dt>{{ __('rfq.ready') }}</dt><dd class="num">{{ isset($q['ready']) ? d(\Illuminate\Support\Carbon::parse($q['ready'])) : '—' }}</dd>
            <dt>{{ __('rfq.price_by') }}</dt><dd class="num">{{ isset($q['price_by']) ? d(\Illuminate\Support\Carbon::parse($q['price_by'])) : '—' }}</dd>
            @if(isset($q['free_days']))<dt>{{ __('rfq.free_days') }}</dt><dd class="num">{{ $q['free_days'] }}</dd>@endif
            <dt>{{ __('rfq.freight_terms') }}</dt><dd>{{ isset($q['freight_terms']) ? __('rfq.'.$q['freight_terms']) : '—' }}</dd>
            <dt>{{ __('rfq.pay') }}</dt><dd>{{ isset($q['pay']) ? __('pay.'.$q['pay']) : '—' }}{{ !empty($q['credit_days']) ? ' · '.$q['credit_days'].' '.__('common.days') : '' }}</dd>
            @if(!empty($q['notes']))<dt>{{ __('common.notes') }}</dt><dd class="text-pre">{{ $q['notes'] }}</dd>@endif
        </dl>
    </x-panel>
    <x-panel :title="__('rfq.check')">
        @if ($probs)
            <div class="gate-box bad"><ul class="mt-0">@foreach ($probs as $p)<li>{{ __($p[0], $p[1]) }}</li>@endforeach</ul></div>
        @else
            <div class="gate-box ok">{{ __('rfq.ok') }}</div>
        @endif
        @if ($file->pricing_status === 'draft' && $u->can('updateRfq', $file))
            <a class="btn btn-primary btn-sm mt-3" href="{{ route('rfq.edit', $file) }}">{{ __('rfq.send') }}</a>
        @endif
    </x-panel>
</div>
