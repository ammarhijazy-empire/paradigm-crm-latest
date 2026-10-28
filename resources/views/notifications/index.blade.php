@extends('layouts.app')
@section('title', __('nav.notifications'))
@section('content')
<x-page-head :title="__('nav.notifications')">
    <x-slot:actions>
        <button class="btn btn-sm btn-light" id="push-enable" data-url="{{ route('push.subscribe') }}" data-done="{{ __('app.push_on') }}" data-fail="{{ __('app.push_failed') }}">{{ __('app.push_enable') }}</button>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-sm btn-light">{{ __('app.mark_all_read') }}</button></form>
    </x-slot:actions>
</x-page-head>
<section class="panel">
    @forelse ($items as $n)
        <a class="notif text-reset text-decoration-none {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifications.open', $n->id) }}"><span class="tl-dot" @if($n->read_at) style="background:var(--line)" @endif></span><div><div>{{ App\Notifications\CrmNotification::text($n->data) }}</div><div class="small-label">{{ d($n->created_at, true) }}</div></div></a>
    @empty <div class="empty">{{ __('common.none') }}</div> @endforelse
    @if ($items->hasPages())<div class="p-3 border-top">{{ $items->links() }}</div>@endif
</section>
@endsection
