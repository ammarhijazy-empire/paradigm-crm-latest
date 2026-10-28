@extends('layouts.app')
@section('title', __('nav.clients'))
@section('content')
<x-page-head :title="__('clients.title')" :sub="auth()->user()->hasRole('sales') && ! auth()->user()->isManagement() ? __('files.only_mine') : null">
    <x-slot:actions>@can('create', App\Models\Client::class)<a class="btn btn-primary btn-sm" href="{{ route('clients.create') }}"><x-icon name="plus" /> {{ __('clients.new') }}</a>@endcan</x-slot:actions>
</x-page-head>
<section class="panel">
    <form class="panel-h" method="GET"><div class="filters">
        <select name="status" class="form-select form-select-sm" data-autosubmit aria-label="{{ __('common.status') }}"><option value="">{{ __('common.status') }}: {{ __('common.all') }}</option>@foreach (App\Models\Client::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('clients.s_'.$s) }}</option>@endforeach</select>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="due" value="1" id="f-due" @checked(request('due')) data-autosubmit><label class="form-check-label small" for="f-due">{{ __('clients.due_followup') }}</label></div>
        <input name="q" class="form-control form-control-sm" style="max-width:220px" value="{{ request('q') }}" placeholder="{{ __('common.search') }}" aria-label="{{ __('common.search') }}">
    </div><span class="small-label">{{ $clients->total() }}</span></form>
    <div class="tbl-wrap"><table class="table table-hover align-middle"><thead><tr><th>{{ __('clients.company') }}</th><th>{{ __('clients.contact_person') }}</th><th>{{ __('clients.sector') }}</th><th>{{ __('common.status') }}</th><th>{{ __('clients.owner') }}</th><th>{{ __('clients.next_follow_up') }}</th></tr></thead><tbody>
    @forelse ($clients as $c)
        <tr><td><a class="row-link" href="{{ route('clients.show', $c) }}">{{ $c->company }}</a><div class="small-label">{{ $c->products }}</div></td>
            <td>{{ $c->contact_person }}<div class="small-label ltr">{{ $c->phone }}</div></td>
            <td class="small">{{ $c->sector === 'other' ? $c->sector_other : __('sectors.'.$c->sector) }}</td>
            <td><x-pill :kind="['lead' => 'info', 'contacted' => 'warn', 'active' => 'ok', 'repeat' => 'rust', 'lost' => 'bad'][$c->status]">{{ __('clients.s_'.$c->status) }}</x-pill></td>
            <td><x-avatar :user="$c->owner" /></td>
            <td class="num">@if($c->next_follow_up && $c->next_follow_up->lte(today()))<x-pill kind="warn">{{ d($c->next_follow_up) }}</x-pill>@else{{ d($c->next_follow_up) }}@endif</td></tr>
    @empty <tr><td colspan="6" class="empty">{{ __('common.none') }}</td></tr> @endforelse
    </tbody></table></div>
    @if ($clients->hasPages())<div class="p-3 border-top">{{ $clients->links() }}</div>@endif
</section>
@endsection
