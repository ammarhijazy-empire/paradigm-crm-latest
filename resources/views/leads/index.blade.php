@extends('layouts.app')
@section('title', __('nav.leads'))
@section('content')
@php $u = auth()->user(); @endphp
<x-page-head :title="__('leads.title')" :sub="__('leads.sub')">
    <x-slot:actions><span class="small-label">{{ __('leads.next') }}: @if($next)<x-avatar :user="$next" /> {{ $next->name }}@else — @endif</span></x-slot:actions>
</x-page-head>
<section class="panel"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('clients.company') }}</th><th>{{ __('leads.message') }}</th><th>{{ __('leads.received') }}</th><th>{{ __('leads.assigned') }}</th><th>{{ __('leads.sla') }}</th><th></th></tr></thead><tbody>
@forelse ($leads as $l)
    <tr><td class="fw-semibold">{{ $l->company }}<div class="small-label">{{ $l->contact_person }} · <span class="ltr">{{ $l->phone }}</span> · <span class="ltr">{{ $l->email }}</span></div></td>
        <td class="small" style="min-width:200px">{{ Str::limit($l->message, 160) }}</td>
        <td class="num small">{{ d($l->received_at, true) }}</td>
        <td>@if($u->isManagement())<form method="POST" action="{{ route('leads.assign', $l) }}">@csrf<select name="user_id" class="form-select form-select-sm" data-autosubmit aria-label="{{ __('leads.assigned') }}">@foreach ($reps as $r)<option value="{{ $r->id }}" @selected($l->assigned_to === $r->id)>{{ $r->name }}</option>@endforeach</select></form>@else<x-avatar :user="$l->assignee" />@endif</td>
        <td>@if($l->contacted_at)<x-pill kind="ok">{{ __('leads.contacted') }}</x-pill>@elseif($l->sla_due_at->isPast())<x-pill kind="bad">{{ __('leads.late_by', ['t' => $l->sla_due_at->diffForHumans(null, true)]) }}</x-pill>@else<x-pill kind="warn">{{ __('leads.left', ['t' => now()->diffForHumans($l->sla_due_at, true)]) }}</x-pill>@endif</td>
        <td class="text-nowrap">
            @if (! $l->contacted_at && ($u->isManagement() || $l->assigned_to === $u->id))<form method="POST" action="{{ route('leads.contacted', $l) }}" class="inline-form">@csrf<button class="btn btn-sm btn-light">{{ __('leads.mark_contacted') }}</button></form>@endif
            @if (! $l->client_id && ($u->isManagement() || $l->assigned_to === $u->id))
                <form method="POST" action="{{ route('leads.convert', $l) }}" class="inline-form d-inline-flex gap-1">@csrf<select name="sector" class="form-select form-select-sm" aria-label="{{ __('clients.sector') }}">@foreach (App\Support\Sectors::KEYS as $s)<option value="{{ $s }}">{{ __('sectors.'.$s) }}</option>@endforeach</select><button class="btn btn-sm btn-primary">{{ __('leads.to_client') }}</button></form>
            @elseif ($l->client)<a class="small" href="{{ route('clients.show', $l->client) }}">{{ $l->client->company }}</a>@endif
        </td></tr>
@empty <tr><td colspan="6" class="empty">{{ __('common.none') }}</td></tr> @endforelse
</tbody></table></div>
<div class="panel-b border-top small"><strong>{{ __('leads.rotation') }}:</strong> {{ $reps->where('gets_leads', true)->pluck('name')->join(' → ') ?: '—' }} · <span class="mono ltr">POST /api/leads</span></div>
@if ($leads->hasPages())<div class="p-3 border-top">{{ $leads->links() }}</div>@endif
</section>
@endsection
