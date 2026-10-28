@extends('layouts.app')
@section('title', __('nav.invoices'))
@section('content')
<x-page-head :title="__('invoice.title')" />
<section class="panel">
    <form class="panel-h" method="GET"><div class="filters"><select name="status" class="form-select form-select-sm" data-autosubmit aria-label="{{ __('common.status') }}"><option value="">{{ __('common.status') }}: {{ __('common.all') }}</option>@foreach (['issued', 'partly_paid', 'paid', 'cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('invoice.s_'.$s) }}</option>@endforeach</select></div></form>
    @include('partials.invoice-table', ['invoices' => $invoices])
    @if ($invoices->hasPages())<div class="p-3 border-top">{{ $invoices->links() }}</div>@endif
</section>
@endsection
