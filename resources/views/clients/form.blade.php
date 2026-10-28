@extends('layouts.app')
@section('title', $client->exists ? $client->company : __('clients.new'))
@section('content')
@php $u = auth()->user(); $canCredit = $u->can('updateCredit', $client); @endphp
<x-page-head :title="$client->exists ? $client->company : __('clients.new')" />
@if (session('duplicates'))
    <div class="alert-flash bad">{{ __('clients.dup_found') }}<ul>@foreach (session('duplicates') as $d)<li>{{ $d->company }} · {{ $d->owner?->name }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}" class="panel" novalidate>
    @csrf @if ($client->exists) @method('PUT') @endif
    @if (session('duplicates'))<input type="hidden" name="confirm_duplicate" value="1">@endif
    <div class="panel-b"><div class="grid g-2">
        <div><label class="form-label" for="company">{{ __('clients.company') }} *</label><input id="company" name="company" class="form-control" value="{{ old('company', $client->company) }}" required></div>
        <div><label class="form-label" for="contact_person">{{ __('clients.contact_person') }} *</label><input id="contact_person" name="contact_person" class="form-control" value="{{ old('contact_person', $client->contact_person) }}" required></div>
        <div><label class="form-label" for="phone">{{ __('clients.phone') }} *</label><input id="phone" name="phone" class="form-control ltr" value="{{ old('phone', $client->phone) }}" required placeholder="+20 1xx xxx xxxx"></div>
        <div><label class="form-label" for="email">{{ __('clients.email') }} *</label><input id="email" name="email" type="email" class="form-control ltr" value="{{ old('email', $client->email) }}" required></div>
        <div class="gate-box bad" id="dup-box" hidden data-url="{{ route('clients.duplicates') }}" data-ignore="{{ $client->id }}" data-label="{{ __('clients.dup_label') }}" style="grid-column: 1 / -1"></div>
        <div><label class="form-label" for="sector">{{ __('clients.sector') }} *</label><select id="sector" name="sector" class="form-select" required>@foreach (App\Support\Sectors::KEYS as $s)<option value="{{ $s }}" @selected(old('sector', $client->sector) === $s)>{{ __('sectors.'.$s) }}</option>@endforeach</select></div>
        <div><label class="form-label" for="sector_other">{{ __('clients.sector_other') }}</label><input id="sector_other" name="sector_other" class="form-control" value="{{ old('sector_other', $client->sector_other) }}"><div class="small-label">{{ __('clients.sector_other_hint') }}</div></div>
        <div><label class="form-label" for="products">{{ __('clients.products') }}</label><input id="products" name="products" class="form-control" value="{{ old('products', $client->products) }}"></div>
        <div><label class="form-label" for="current_forwarder">{{ __('clients.current_forwarder') }}</label><input id="current_forwarder" name="current_forwarder" class="form-control" value="{{ old('current_forwarder', $client->current_forwarder) }}"></div>
        <div><label class="form-label" for="source">{{ __('clients.source') }}</label><input id="source" name="source" class="form-control" value="{{ old('source', $client->source) }}"></div>
        <div><label class="form-label" for="status">{{ __('common.status') }}</label><select id="status" name="status" class="form-select">@foreach (App\Models\Client::STATUSES as $s)<option value="{{ $s }}" @selected(old('status', $client->status) === $s)>{{ __('clients.s_'.$s) }}</option>@endforeach</select></div>
        <div><label class="form-label" for="next_follow_up">{{ __('clients.next_follow_up') }}</label><input id="next_follow_up" type="date" name="next_follow_up" class="form-control" value="{{ old('next_follow_up', $client->next_follow_up?->toDateString()) }}"></div>
        <div><label class="form-label" for="next_order_date">{{ __('clients.next_order') }}</label><input id="next_order_date" type="date" name="next_order_date" class="form-control" value="{{ old('next_order_date', $client->next_order_date?->toDateString()) }}"></div>
        <div><label class="form-label" for="tax_id">{{ __('clients.tax_id') }}</label><input id="tax_id" name="tax_id" class="form-control mono" value="{{ old('tax_id', $client->tax_id) }}"></div>
        <div><label class="form-label" for="address">{{ __('clients.address') }}</label><input id="address" name="address" class="form-control" value="{{ old('address', $client->address) }}"></div>
        @if ($canCredit)
            <div><label class="form-label" for="credit_limit">{{ __('clients.credit_limit') }} (USD)</label><input id="credit_limit" type="number" min="0" step="0.01" name="credit_limit" class="form-control num" value="{{ old('credit_limit', $client->credit_limit) }}"></div>
            <div><label class="form-label" for="credit_days">{{ __('clients.credit_days') }}</label><input id="credit_days" type="number" min="0" name="credit_days" class="form-control num" value="{{ old('credit_days', $client->credit_days) }}"></div>
        @endif
        @if ($u->isManagement())
            <div><label class="form-label" for="owner_id">{{ __('clients.owner') }}</label><select id="owner_id" name="owner_id" class="form-select">@foreach ($owners as $o)<option value="{{ $o->id }}" @selected(old('owner_id', $client->owner_id) == $o->id)>{{ $o->name }}</option>@endforeach</select></div>
        @endif
    </div></div>
    <div class="panel-b border-top d-flex gap-2"><button class="btn btn-primary">{{ session('duplicates') ? __('clients.save_anyway') : __('common.save') }}</button><a class="btn btn-light" href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}">{{ __('common.cancel') }}</a></div>
</form>
@endsection
