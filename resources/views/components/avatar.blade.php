@props(['user'])
<span class="avatar" title="{{ $user?->name }}">{{ $user?->displayInitials() ?? '?' }}</span>
