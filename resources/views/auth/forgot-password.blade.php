@extends('layouts.guest')
@section('title', __('auth.forgot'))
@section('content')
<h1>{{ __('auth.forgot') }}</h1>
<p class="muted mb-3">{{ __('auth.forgot_sub') }}</p>
@if (session('status'))<div class="alert-flash ok">{{ session('status') }}</div>@endif
@include('partials.flash')
<form method="POST" action="{{ route('password.email') }}">@csrf
    <div class="mb-3"><label class="form-label" for="email">{{ __('auth.email') }}</label><input id="email" name="email" type="email" class="form-control ltr" value="{{ old('email') }}" required></div>
    <button class="btn btn-primary w-100">{{ __('auth.send_link') }}</button>
    <div class="small mt-3"><a href="{{ route('login') }}">{{ __('common.back') }}</a></div>
</form>
@endsection
