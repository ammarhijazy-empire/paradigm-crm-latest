@extends('layouts.guest')
@section('title', __('auth.login_title'))
@section('content')
<h1>{{ __('auth.login_title') }}</h1>
<p class="muted mb-3">{{ __('auth.login_sub', ['domain' => config('crm.signup_domain')]) }}</p>
@include('partials.flash')
<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="mb-3"><label class="form-label" for="email">{{ __('auth.email') }}</label>
        <input id="email" name="email" type="email" class="form-control ltr" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
    <div class="mb-2"><label class="form-label" for="password">{{ __('auth.password') }}</label>
        <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password"></div>
    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label small" for="remember">{{ __('auth.remember') }}</label></div>
    <button class="btn btn-primary w-100 mt-2">{{ __('auth.login_btn') }}</button>
    <div class="d-flex justify-content-between mt-3 small">
        <a href="{{ route('password.request') }}">{{ __('auth.forgot') }}</a>
        <a href="{{ route('register') }}">{{ __('auth.register_link') }}</a>
    </div>
</form>
@endsection
