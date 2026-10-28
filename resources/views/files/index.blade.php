@extends('layouts.app')
@section('title', __('files.title'))
@section('content')
<x-page-head :title="__('files.title')" :sub="__('files.sub').(auth()->user()->isManagement() || ! auth()->user()->hasRole('sales') ? '' : ' · '.__('files.only_mine'))">
    <x-slot:actions>@can('create', App\Models\ShipmentFile::class)<a class="btn btn-primary btn-sm" href="{{ route('rfq.create') }}"><x-icon name="plus" /> {{ __('nav.rfq') }}</a>@endcan</x-slot:actions>
</x-page-head>
<section class="panel">
    <form class="panel-h" method="GET">
        <div class="filters">
            <select name="stage" class="form-select form-select-sm" aria-label="{{ __('files.stage') }}" data-autosubmit>
                <option value="">{{ __('files.stage') }}: {{ __('common.all') }}</option>
                @foreach (range(1, 7) as $n)<option value="{{ $n }}" @selected(request('stage') == $n)>{{ $n }} · {{ __('stages.'.$n) }}</option>@endforeach
            </select>
            <select name="status" class="form-select form-select-sm" aria-label="{{ __('files.status') }}" data-autosubmit>
                @foreach (['open', 'closed', 'lost', ''] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ __('files.status') }}: {{ $s ? __('files.s_'.$s) : __('common.all') }}</option>@endforeach
            </select>
            @if (auth()->user()->isManagement())
                <select name="sales" class="form-select form-select-sm" aria-label="{{ __('files.sales') }}" data-autosubmit>
                    <option value="">{{ __('files.sales') }}: {{ __('common.all') }}</option>
                    @foreach ($salesUsers as $s)<option value="{{ $s->id }}" @selected(request('sales') == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
            @endif
            <input name="q" class="form-control form-control-sm" style="max-width:220px" value="{{ request('q') }}" placeholder="{{ __('app.search_ph') }}" aria-label="{{ __('app.search_ph') }}">
        </div>
        <span class="small-label">{{ $files->total() }}</span>
    </form>
    <div class="panel-b flush">@include('partials.file-table', ['files' => $files, 'days' => true])</div>
    @if ($files->hasPages())<div class="p-3 border-top">{{ $files->links() }}</div>@endif
</section>
@endsection
