@extends('layouts.app')
@section('title', __('nav.ai'))
@section('content')
<x-page-head :title="__('ai.usage')" :sub="__('settings.ai_model').': '.config('crm.ai.model').' · '.($configured ? __('settings.ai_connected') : __('settings.ai_not_configured'))" />
<div class="grid g-4 mb-3">
    @php $pc = $limit > 0 ? min(100, round($spent / $limit * 100)) : 0; @endphp
    <div class="panel kpi"><div class="lbl">{{ __('ai.spent') }}</div><div class="val ltr">{{ money($spent) }}</div><div class="note ltr">/ {{ money($limit) }} · {{ $pc }}%</div></div>
    @foreach (['whatsapp_rfq', 'vendor_email', 'doc_check'] as $f)<div class="panel kpi"><div class="lbl">{{ __('ai.f_'.$f) }}</div><div class="val">{{ $byFeature[$f]->n ?? 0 }}</div><div class="note ltr">{{ money($byFeature[$f]->cost ?? 0) }}</div></div>@endforeach
</div>
<section class="panel"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('common.date') }}</th><th>{{ __('ai.feature') }}</th><th>{{ __('ai.user') }}</th><th>{{ __('files.file') }}</th><th>{{ __('ai.model') }}</th><th>{{ __('ai.tokens') }}</th><th>{{ __('ai.cost') }}</th><th>{{ __('common.status') }}</th></tr></thead><tbody>
@forelse ($runs as $r)
    <tr><td class="num small">{{ d($r->created_at, true) }}</td><td class="small">{{ __('ai.f_'.$r->feature) }}</td><td>@if($r->user)<x-avatar :user="$r->user" />@else — @endif</td><td class="mono small">@if($r->file)<a href="{{ route('files.show', $r->file) }}">{{ $r->file->number }}</a>@else — @endif</td>
        <td class="small mono">{{ $r->model ?? '—' }}</td><td class="num">{{ number_format($r->tokens_in + $r->tokens_out) }}</td><td class="num">{{ number_format($r->cost_usd, 4) }}</td>
        <td><x-pill :kind="['accepted' => 'ok', 'done' => 'info', 'failed' => 'bad', 'rejected' => 'bad', 'queued' => '', 'running' => 'warn'][$r->status]" :title="$r->error">{{ __('ai.s_'.$r->status) }}</x-pill></td></tr>
@empty <tr><td colspan="8" class="empty">{{ __('common.none') }}</td></tr> @endforelse
</tbody></table></div>@if ($runs->hasPages())<div class="p-3 border-top">{{ $runs->links() }}</div>@endif</section>
@endsection
