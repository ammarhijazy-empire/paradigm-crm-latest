@extends('layouts.guest')
@section('title', __('auth.waiting_title'))
@section('content')
<h1>{{ __('auth.waiting_title') }}</h1>
<p class="muted">{{ __('auth.waiting_body') }}</p>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light w-100 mt-2">{{ __('auth.sign_out') }}</button></form>
@endsection
