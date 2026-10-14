<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-dark app-navbar shadow-sm">
        <div class="container-fluid page-shell px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">ع</span>
                <span>{{ __('app.name') }}</span>
            </a>
        </div>
    </nav>

    <main class="container-fluid page-shell px-3 px-lg-4 py-4 py-lg-5">
        @yield('content')
    </main>

    <footer class="container-fluid page-shell px-3 px-lg-4 pb-4 text-secondary small">
        {{ __('app.foundation.scope_note') }}
    </footer>

    @stack('scripts')
</body>
</html>
