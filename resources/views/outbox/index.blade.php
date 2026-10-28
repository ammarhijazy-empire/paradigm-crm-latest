@extends('layouts.app')
@section('title', __('nav.outbox'))
@section('content')
<x-page-head :title="__('outbox.title')" :sub="__('outbox.sub')" />
<section class="panel">
    <form class="panel-h" method="GET"><div class="filters"><select name="status" class="form-select form-select-sm" data-autosubmit aria-label="{{ __('common.status') }}"><option value="">{{ __('common.all') }}</option>@foreach (['queued', 'sent', 'failed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('outbox.s_'.$s) }}</option>@endforeach</select></div></form>
    <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('common.date') }}</th><th>{{ __('outbox.to') }}</th><th>{{ __('pricing.subject') }}</th><th>{{ __('outbox.kind') }}</th><th>{{ __('common.status') }}</th><th></th></tr></thead><tbody>
    @forelse ($rows as $r)
        <tr><td class="num small">{{ d($r->created_at, true) }}</td><td class="ltr small">{{ $r->to }}</td><td class="small">{{ Str::limit($r->subject, 70) }}@if($r->error)<div class="text-danger small">{{ Str::limit($r->error, 140) }}</div>@endif</td><td class="small">{{ $r->kind }}</td>
            <td><x-pill :kind="['sent' => 'ok', 'failed' => 'bad', 'queued' => 'warn'][$r->status]">{{ __('outbox.s_'.$r->status) }}</x-pill> <span class="small-label">×{{ $r->attempts }}</span></td>
            <td>@if($r->status === 'failed')<form method="POST" action="{{ route('outbox.retry', $r) }}">@csrf<button class="btn btn-sm btn-light">{{ __('outbox.retry') }}</button></form>@endif</td></tr>
    @empty <tr><td colspan="6" class="empty">{{ __('common.none') }}</td></tr> @endforelse
    </tbody></table></div>
    @if ($rows->hasPages())<div class="p-3 border-top">{{ $rows->links() }}</div>@endif
</section>
@endsection
