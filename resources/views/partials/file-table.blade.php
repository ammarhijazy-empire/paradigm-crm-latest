@php $days = $days ?? false; $wait = (int) App\Support\Settings::get('stage_wait_days'); @endphp
@if ($files->isEmpty())
    <div class="empty">{{ __('common.none') }}</div>
@else
<div class="tbl-wrap"><table class="table table-hover align-middle">
    <thead><tr><th>{{ __('files.file') }}</th><th>{{ __('files.client') }}</th><th>{{ __('files.service') }}</th><th>{{ __('files.route') }}</th><th>{{ __('files.stage') }}</th>@if($days)<th>{{ __('files.days_in_stage') }}</th>@endif<th>{{ __('files.sales') }}</th><th>{{ __('files.eta') }}</th></tr></thead>
    <tbody>
    @foreach ($files as $f)
        @php $dd = App\Support\Dates::workingDaysSince($f->stage_entered_at); @endphp
        <tr>
            <td><a class="row-link mono" href="{{ route('files.show', $f) }}">{{ $f->number }}</a><div class="small-label mono">{{ $f->ref }}</div></td>
            <td class="client-cell">{{ $f->client?->company }}</td>
            <td class="svc">{{ __('services.'.$f->service) }}@if($f->addons)<div class="small-label">+ {{ collect($f->addons)->map(fn ($a) => __('services.'.$a))->join('، ') }}</div>@endif</td>
            <td class="small route">{{ port_name($f->origin) }} <span class="muted">→</span> {{ port_name($f->destination) }}</td>
            <td>@if($f->status === 'lost')<x-pill kind="bad">{{ __('files.s_lost') }}</x-pill>@elseif($f->status === 'closed')<x-pill kind="ok">{{ __('files.s_closed') }}</x-pill>@else<x-stage :n="$f->stage" />@endif</td>
            @if($days)<td>@if($f->status === 'open' && $dd > $wait)<x-pill kind="warn">{{ $dd }} {{ __('common.days') }}</x-pill>@else<span class="num">{{ $dd }}</span>@endif</td>@endif
            <td><x-avatar :user="$f->sales" /></td>
            <td class="num">{{ d($f->eta) }}</td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
