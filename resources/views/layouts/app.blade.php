@php
    $u = auth()->user();
    $unread = $u->unreadNotifications()->count();
    $latest = $u->notifications()->limit(8)->get();
    $nav = [
        'g_work' => [
            ['dashboard', 'nav.dashboard', 'home', true],
            ['files.index', 'nav.files', 'box', $u->can('viewAny', App\Models\ShipmentFile::class)],
            ['rfq.create', 'nav.rfq', 'plus', $u->can('create', App\Models\ShipmentFile::class)],
            ['pricing.index', 'nav.pricing', 'tag', $u->actsAs('pricing') || $u->actsAs('pricing_review')],
            ['files.operations', 'nav.ops', 'ship', $u->actsAs('ops') || $u->actsAs('customs')],
        ],
        'g_people' => [
            ['clients.index', 'nav.clients', 'users', $u->can('viewAny', App\Models\Client::class)],
            ['leads.index', 'nav.leads', 'spark', $u->can('viewAny', App\Models\Lead::class)],
            ['vendors.index', 'nav.vendors', 'truck', $u->can('viewAny', App\Models\Vendor::class)],
        ],
        'g_money' => [
            ['invoices.index', 'nav.invoices', 'file', $u->can('viewAny', App\Models\Invoice::class)],
            ['collections.index', 'nav.collections', 'coins', $u->actsAs('accounts')],
            ['performance.index', 'nav.performance', 'chart', $u->actsAs('sales')],
        ],
        'g_admin' => [
            ['users.index', 'nav.users', 'key', $u->isManagement()],
            ['settings.edit', 'nav.settings', 'gear', $u->isManagement()],
            ['ai.usage', 'nav.ai', 'spark', $u->isManagement()],
            ['outbox.index', 'nav.outbox', 'inbox', $u->isManagement()],
        ],
    ];
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>@yield('title', __('app.name')) · Paradigm Cargo</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap">
    @vite([rtl() ? 'resources/css/bootstrap-rtl.css' : 'resources/css/bootstrap-ltr.css', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="shell">
    <nav class="rail" id="rail" aria-label="{{ __('nav.menu') }}">
        <div class="brand"><span class="brand-mark">PC</span><span>Paradigm Cargo</span></div>
        @foreach ($nav as $group => $items)
            @php $vis = array_filter($items, fn ($i) => $i[3]); @endphp
            @if ($vis)
                <div class="group">{{ __('nav.'.$group) }}</div>
                @foreach ($vis as [$route, $label, $icon])
                    <a href="{{ route($route) }}" class="{{ request()->routeIs(Str::before($route, '.').'*') || (request()->routeIs($route)) ? 'active' : '' }}">
                        <x-icon :name="$icon" /><span>{{ __($label) }}</span>
                    </a>
                @endforeach
            @endif
        @endforeach
        <div class="foot">
            {{ $u->email }}<br>
            <span>{{ $u->getRoleNames()->map(fn ($r) => __('roles.'.$r))->join('، ') }}</span>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button class="btn btn-link p-0 text-reset small">{{ __('auth.sign_out') }}</button></form>
        </div>
    </nav>
    <div class="scrim" id="scrim" hidden></div>
    <div style="min-width:0">
        @if (config('crm.staging_banner'))<div class="staging-banner">{{ __('app.staging') }}</div>@endif
        <header class="topbar">
            <button class="btn btn-light btn-sm menu-btn" id="menu-btn" type="button" aria-label="{{ __('nav.menu') }}" aria-controls="rail"><x-icon name="menu" /></button>
            <form class="search" method="GET" action="{{ route('files.index') }}" role="search">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent"><x-icon name="search" /></span>
                    <input name="q" class="form-control" type="search" value="{{ request('q') }}" placeholder="{{ __('app.search_ph') }}" aria-label="{{ __('app.search_ph') }}">
                    <input type="hidden" name="status" value="">
                </div>
            </form>
            <div class="spacer"></div>
            <form method="POST" action="{{ route('locale') }}">@csrf
                <input type="hidden" name="locale" value="{{ rtl() ? 'en' : 'ar' }}">
                <button class="btn btn-light btn-sm"><x-icon name="globe" /> {{ rtl() ? 'English' : 'العربية' }}</button>
            </form>
            <div class="dropdown">
                <button class="btn btn-light btn-sm bell" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('nav.notifications') }}">
                    <x-icon name="bell" />@if ($unread)<span class="dot">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width:min(360px,92vw)">
                    <div class="split px-3 py-2 border-bottom"><strong class="small">{{ __('nav.notifications') }}</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-link btn-sm p-0">{{ __('app.mark_all_read') }}</button></form></div>
                    @forelse ($latest as $n)
                        <a class="notif text-reset text-decoration-none {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifications.open', $n->id) }}">
                            <span class="tl-dot" @if($n->read_at) style="background:var(--line)" @endif></span>
                            <div><div class="small">{{ App\Notifications\CrmNotification::text($n->data) }}</div><div class="small-label">{{ d($n->created_at, true) }}</div></div>
                        </a>
                    @empty
                        <div class="empty">{{ __('common.none') }}</div>
                    @endforelse
                    <a class="d-block text-center small py-2" href="{{ route('notifications.index') }}">{{ __('app.all_notifications') }}</a>
                </div>
            </div>
            <span class="avatar" title="{{ $u->name }}">{{ $u->displayInitials() }}</span>
        </header>
        <main class="main" id="main">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@stack('modals')
</body>
</html>
