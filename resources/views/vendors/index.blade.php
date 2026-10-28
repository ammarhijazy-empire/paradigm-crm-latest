@extends('layouts.app')
@section('title', __('nav.vendors'))
@section('content')
<x-page-head :title="__('vendors.title')">
    <x-slot:actions>@can('create', App\Models\Vendor::class)<a class="btn btn-primary btn-sm" href="{{ route('vendors.create') }}"><x-icon name="plus" /> {{ __('vendors.new') }}</a>@endcan</x-slot:actions>
</x-page-head>
<section class="panel">
    <form class="panel-h" method="GET"><div class="filters"><select name="type" class="form-select form-select-sm" data-autosubmit aria-label="{{ __('vendors.type') }}"><option value="">{{ __('vendors.type') }}: {{ __('common.all') }}</option>@foreach (['agent', 'line', 'air', 'cfs', 'broker', 'truck', 'courier', 'insurance', 'other'] as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ __('vendors.t_'.$t) }}</option>@endforeach</select></div><span class="small-label">{{ $vendors->count() }}</span></form>
    <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('vendors.name') }}</th><th>{{ __('vendors.type') }}</th><th>{{ __('vendors.country') }}</th><th>{{ __('clients.email') }}</th><th>{{ __('vendors.quotes') }}</th><th>{{ __('vendors.used') }}</th><th></th></tr></thead><tbody>
    @forelse ($vendors as $v)
        <tr @class(['text-muted' => ! $v->active])><td class="fw-semibold">{{ $v->name }}<div class="small-label">{{ $v->contact_person }}</div></td><td><x-pill>{{ __('vendors.t_'.$v->type) }}</x-pill></td><td>{{ $v->country }}</td><td class="ltr small">{{ $v->email }}</td><td class="num">{{ $v->quotes_count }}</td><td class="num">{{ $v->used_count }}</td>
            <td>@can('update', $v)<a class="btn btn-sm btn-light" href="{{ route('vendors.edit', $v) }}">{{ __('common.edit') }}</a>@endcan</td></tr>
    @empty <tr><td colspan="7" class="empty">{{ __('common.none') }}</td></tr> @endforelse
    </tbody></table></div>
</section>
@endsection
