@extends('emails.layout')
@section('body')
<p><strong>{{ $heading }}</strong></p>
@foreach ($lines as $l)<p style="margin:4px 0">{{ $l }}</p>@endforeach
@if (!empty($url))<p style="margin-top:14px"><a href="{{ $url }}" style="background:#c2531b;color:#fff;padding:8px 14px;border-radius:5px;text-decoration:none">{{ __('common.open') }}</a></p>@endif
@endsection
