<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title') · Paradigm Cargo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap">
    @vite([rtl() ? 'resources/css/bootstrap-rtl.css' : 'resources/css/bootstrap-ltr.css', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@if (config('crm.staging_banner'))<div class="staging-banner">{{ __('app.staging') }}</div>@endif
<div class="login-wrap"><div class="login-card">
    <div class="d-flex align-items-center gap-2 mb-3"><span class="brand-mark">PC</span><strong>Paradigm Cargo</strong>
        <form method="POST" action="{{ route('locale') }}" class="ms-auto">@csrf<input type="hidden" name="locale" value="{{ rtl() ? 'en' : 'ar' }}">
            <button class="btn btn-light btn-sm"><x-icon name="globe" /> {{ rtl() ? 'English' : 'العربية' }}</button></form>
    </div>
    @yield('content')
</div></div>
</body>
</html>
