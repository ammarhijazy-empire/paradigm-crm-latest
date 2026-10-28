@extends('layouts.guest')
@section('title', __('auth.register_title'))
@section('content')
<h1>{{ __('auth.register_title') }}</h1>
<p class="muted mb-3">{{ __('auth.register_sub', ['domain' => config('crm.signup_domain')]) }}</p>
@include('partials.flash')
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf
    <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
    <div class="mb-3"><label class="form-label" for="name">{{ __('auth.name') }}</label><input id="name" name="name" class="form-control" value="{{ old('name') }}" required autocomplete="name"></div>
    <div class="mb-3"><label class="form-label" for="email">{{ __('auth.email') }}</label><input id="email" name="email" type="email" class="form-control ltr" value="{{ old('email') }}" required placeholder="name@{{ config('crm.signup_domain') }}" autocomplete="username"></div>
    <div class="mb-3"><label class="form-label" for="password">{{ __('auth.password') }}</label><input id="password" name="password" type="password" class="form-control" required autocomplete="new-password"><div class="small-label mt-1">{{ __('auth.password_rule') }}</div></div>
    <div class="mb-3"><label class="form-label" for="password_confirmation">{{ __('auth.password_confirm') }}</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password"></div>
    <button class="btn btn-primary w-100">{{ __('auth.register_btn') }}</button>
    <div class="small mt-3"><a href="{{ route('login') }}">{{ __('auth.have_account') }}</a></div>
</form>
@endsection
