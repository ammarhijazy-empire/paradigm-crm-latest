@extends('layouts.guest')
@section('title', __('auth.reset_title'))
@section('content')
<h1>{{ __('auth.reset_title') }}</h1>
@include('partials.flash')
<form method="POST" action="{{ route('password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="mb-3"><label class="form-label" for="email">{{ __('auth.email') }}</label><input id="email" name="email" type="email" class="form-control ltr" value="{{ old('email', $request->email) }}" required></div>
    <div class="mb-3"><label class="form-label" for="password">{{ __('auth.password') }}</label><input id="password" name="password" type="password" class="form-control" required autocomplete="new-password"></div>
    <div class="mb-3"><label class="form-label" for="password_confirmation">{{ __('auth.password_confirm') }}</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password"></div>
    <button class="btn btn-primary w-100">{{ __('auth.reset_btn') }}</button>
</form>
@endsection
