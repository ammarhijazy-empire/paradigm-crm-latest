@extends('layouts.app')
@section('title', $vendor->exists ? $vendor->name : __('vendors.new'))
@section('content')
<x-page-head :title="$vendor->exists ? $vendor->name : __('vendors.new')" />
<form method="POST" action="{{ $vendor->exists ? route('vendors.update', $vendor) : route('vendors.store') }}" class="panel">@csrf @if ($vendor->exists) @method('PUT') @endif
    <div class="panel-b"><div class="grid g-2">
        <div><label class="form-label" for="name">{{ __('vendors.name') }} *</label><input id="name" name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required></div>
        <div><label class="form-label" for="type">{{ __('vendors.type') }} *</label><select id="type" name="type" class="form-select">@foreach (['agent', 'line', 'air', 'cfs', 'broker', 'truck', 'courier', 'insurance', 'other'] as $t)<option value="{{ $t }}" @selected(old('type', $vendor->type) === $t)>{{ __('vendors.t_'.$t) }}</option>@endforeach</select></div>
        <div><label class="form-label" for="country">{{ __('vendors.country') }} (ISO)</label><input id="country" name="country" maxlength="2" class="form-control ltr" value="{{ old('country', $vendor->country) }}" placeholder="CN"></div>
        <div><label class="form-label" for="contact_person">{{ __('clients.contact_person') }}</label><input id="contact_person" name="contact_person" class="form-control" value="{{ old('contact_person', $vendor->contact_person) }}"></div>
        <div><label class="form-label" for="email">{{ __('clients.email') }}</label><input id="email" type="email" name="email" class="form-control ltr" value="{{ old('email', $vendor->email) }}"><div class="small-label">{{ __('vendors.email_hint') }}</div></div>
        <div><label class="form-label" for="phone">{{ __('clients.phone') }}</label><input id="phone" name="phone" class="form-control ltr" value="{{ old('phone', $vendor->phone) }}"></div>
    </div>
    <label class="form-label mt-2" for="notes">{{ __('common.notes') }}</label><textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes', $vendor->notes) }}</textarea>
    @if ($vendor->exists)<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked($vendor->active)><label class="form-check-label" for="active">{{ __('common.active') }}</label></div>@endif
    </div>
    <div class="panel-b border-top d-flex gap-2"><button class="btn btn-primary">{{ __('common.save') }}</button><a class="btn btn-light" href="{{ route('vendors.index') }}">{{ __('common.cancel') }}</a></div>
</form>
@endsection
