@extends('emails.layout')
@section('body')
<p>{{ __('pricing.cost_ready_body', ['ref' => $file->ref]) }}</p>
<p><a href="{{ route('files.show', $file) }}?tab=quote" style="background:#c2531b;color:#fff;padding:8px 14px;border-radius:5px;text-decoration:none">{{ __('common.open') }}</a></p>
@endsection
