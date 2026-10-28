@extends('emails.layout')
@section('body')
<p><strong>{{ __('shipping.k_'.$kind) }}</strong> · {{ $file->number }}</p>
<div style="white-space:pre-wrap">{{ $body }}</div>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:13px;margin-top:12px">
<tr><td style="background:#f3f5f5;width:38%">{{ __('files.bl') }}</td><td dir="ltr">{{ $file->bl_no ?: '—' }}</td></tr>
<tr><td style="background:#f3f5f5">{{ __('files.carrier') }}</td><td>{{ $file->carrier ?: '—' }}</td></tr>
<tr><td style="background:#f3f5f5">{{ __('files.etd') }}</td><td>{{ d($file->atd ?? $file->etd) }}</td></tr>
<tr><td style="background:#f3f5f5">{{ __('files.eta') }}</td><td>{{ d($file->eta) }}</td></tr>
</table>
@endsection
