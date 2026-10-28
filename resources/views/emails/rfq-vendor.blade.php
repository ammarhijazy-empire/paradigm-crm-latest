@extends('emails.layout')
@section('body')
{{-- Sent to vendors. Never includes client contact details. --}}
@php $q = $file->inq ?? []; @endphp
<p>Dear {{ $vendor->contact_person ?: $vendor->name }},</p>
<p>Please quote your best rate for the shipment below. Kindly keep the reference <strong>{{ $file->ref }}-OPS</strong> in the subject of your reply.</p>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:13px">
<tr><td style="background:#f3f5f5;width:38%">Service</td><td>{{ __('services.'.$file->service, [], 'en') }}{{ $file->addons ? ' + '.collect($file->addons)->map(fn ($a) => __('services.'.$a, [], 'en'))->join(', ') : '' }}</td></tr>
<tr><td style="background:#f3f5f5">Direction</td><td>{{ ucfirst($file->direction) }}</td></tr>
<tr><td style="background:#f3f5f5">Origin</td><td>{{ $file->origin }} @if($file->pickup_address)— pickup: {{ $file->pickup_address }}@endif</td></tr>
<tr><td style="background:#f3f5f5">Destination</td><td>{{ $file->destination }} @if($file->delivery_address)— delivery: {{ $file->delivery_address }}@endif</td></tr>
<tr><td style="background:#f3f5f5">Incoterm</td><td>{{ $file->incoterm }}</td></tr>
<tr><td style="background:#f3f5f5">Volume</td><td>@include('partials.volume', ['f' => $file])</td></tr>
<tr><td style="background:#f3f5f5">Commodity</td><td>{{ $q['commodity'] ?? '—' }} ({{ __('cargo.'.($q['cargo'] ?? 'general'), [], 'en') }})@if(($q['cargo'] ?? '') === 'hazardous') — UN {{ $q['un'] ?? '' }}, IMO {{ $q['imo'] ?? '' }}, PG {{ $q['pg'] ?? '' }}@endif @if(($q['cargo'] ?? '') === 'reefer') — {{ $q['temp'] ?? '' }} °C @endif</td></tr>
<tr><td style="background:#f3f5f5">Cargo ready</td><td>{{ $q['ready'] ?? '—' }}</td></tr>
@if (!empty($q['free_days']))<tr><td style="background:#f3f5f5">Free time required</td><td>{{ $q['free_days'] }} days</td></tr>@endif
<tr><td style="background:#f3f5f5">Freight</td><td>{{ ucfirst($q['freight_terms'] ?? '—') }}</td></tr>
</table>
@if ($note)<p style="margin-top:14px">{{ $note }}</p>@endif
<p style="margin-top:14px">Please include: all surcharges, origin and destination charges, transit time, free time, validity and ETD.</p>
<p>Best regards,<br>Pricing team · Paradigm Cargo</p>
@endsection
