@php $rows = $f->inq['rows'] ?? []; $s = $f->service; @endphp
@foreach ($rows as $r)
    @if (in_array($s, ['fcl', 'se']))
        {{ $r['count'] ?? 0 }}×{{ $r['type'] ?? '' }}
    @elseif (in_array($s, ['lcl', 'air', 'courier']))
        {{ $r['pieces'] ?? 0 }} pcs · {{ $r['l'] ?? 0 }}×{{ $r['w'] ?? 0 }}×{{ $r['h'] ?? 0 }} cm · {{ number_format((float) ($r['kg'] ?? 0)) }} kg · <b>{{ number_format(App\Support\Chargeable::for($s, $r), $s === 'lcl' ? 3 : 1) }} {{ $s === 'lcl' ? 'W/M' : 'kg ch.' }}</b>
    @elseif ($s === 'land')
        {{ $r['count'] ?? 0 }}× {{ $r['truck'] ?? '' }} · {{ number_format((float) ($r['kg'] ?? 0)) }} kg
    @elseif ($s === 'bulk')
        {{ number_format((float) ($r['tonnes'] ?? 0), 2) }} t
    @elseif ($s === 'roro')
        {{ $r['count'] ?? 0 }} units · {{ $r['l'] ?? 0 }}×{{ $r['w'] ?? 0 }}×{{ $r['h'] ?? 0 }} cm · {{ number_format((float) ($r['kg'] ?? 0)) }} kg
    @endif
    @if (! $loop->last)<br>@endif
@endforeach
@if (! $rows) — @endif
