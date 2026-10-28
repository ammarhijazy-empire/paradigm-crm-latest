@extends('layouts.app')
@section('title', __('nav.users'))
@section('content')
<x-page-head :title="__('users.title')" :sub="__('users.sub')" />
<div class="ai-box mb-3 small">{{ __('users.admin_note') }}</div>
<div class="stack">
@foreach ($users as $usr)
    <form method="POST" action="{{ route('users.update', $usr) }}" class="panel">@csrf @method('PUT')
        <div class="panel-h"><h2><x-avatar :user="$usr" /> {{ $usr->name }} <span class="small-label ltr">{{ $usr->email }}</span></h2>
            <div>@if($usr->roles->isEmpty())<x-pill kind="bad">{{ __('users.no_role') }}</x-pill>@endif @unless($usr->active)<x-pill>{{ __('users.inactive') }}</x-pill>@endunless</div></div>
        <div class="panel-b">
            <div class="grid g-4">
                <div><label class="form-label" for="u{{ $usr->id }}-name">{{ __('auth.name') }}</label><input id="u{{ $usr->id }}-name" name="name" class="form-control form-control-sm" value="{{ $usr->name }}" required></div>
                <div><label class="form-label" for="u{{ $usr->id }}-ini">{{ __('users.initials') }}</label><input id="u{{ $usr->id }}-ini" name="initials" maxlength="4" class="form-control form-control-sm mono" value="{{ $usr->initials }}"></div>
                <div><label class="form-label" for="u{{ $usr->id }}-target">{{ __('perf.target') }} (USD)</label><input id="u{{ $usr->id }}-target" type="number" min="0" step="0.01" name="sales_target" class="form-control form-control-sm num" value="{{ $usr->sales_target }}"></div>
                <div><label class="form-label" for="u{{ $usr->id }}-com">{{ __('perf.commission') }} %</label><input id="u{{ $usr->id }}-com" type="number" min="0" max="100" step="0.01" name="commission_pct" class="form-control form-control-sm num" value="{{ $usr->commission_pct }}"></div>
            </div>
            <div class="form-label mt-3">{{ __('users.roles') }}</div>
            <div class="d-flex flex-wrap gap-1">@foreach (App\Models\User::ROLES as $r)<input type="checkbox" class="btn-check" name="roles[]" value="{{ $r }}" id="u{{ $usr->id }}-r-{{ $r }}" @checked($usr->hasRole($r))><label class="btn btn-sm btn-outline-primary" for="u{{ $usr->id }}-r-{{ $r }}">{{ __('roles.'.$r) }}</label>@endforeach</div>
            <div class="d-flex gap-3 mt-3 flex-wrap">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="active" value="1" id="u{{ $usr->id }}-active" @checked($usr->active)><label class="form-check-label" for="u{{ $usr->id }}-active">{{ __('common.active') }}</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="gets_leads" value="1" id="u{{ $usr->id }}-leads" @checked($usr->gets_leads)><label class="form-check-label" for="u{{ $usr->id }}-leads">{{ __('users.gets_leads') }}</label></div>
                <button class="btn btn-sm btn-primary ms-auto">{{ __('common.save') }}</button>
            </div>
        </div>
    </form>
@endforeach
</div>
@endsection
