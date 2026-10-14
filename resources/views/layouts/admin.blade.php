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
            <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="{{ auth()->check() ? route('admin.dashboard') : route('home') }}">
                <span class="brand-mark" aria-hidden="true">ع</span>
                <span>{{ __('app.name') }}</span>
            </a>

            <div class="d-flex align-items-center gap-2 text-white">
                @auth
                    @can(\App\Enums\PermissionEnum::USER_LIST->value)
                        <a class="btn btn-sm btn-outline-light" href="{{ route('admin.users.index') }}">
                            {{ __('app.users.title') }}
                        </a>
                    @endcan
                    <span class="d-none d-md-inline small">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-light" type="submit">{{ __('app.auth.logout') }}</button>
                    </form>
                @else
                    <a class="btn btn-sm btn-light" href="{{ route('login') }}">{{ __('app.auth.login') }}</a>
                @endauth
            </div>
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
