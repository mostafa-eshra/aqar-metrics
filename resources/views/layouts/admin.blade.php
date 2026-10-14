<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#176b5b">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title', __('app.name'))</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('aqar-theme') ?? 'auto';
            const resolvedTheme = savedTheme === 'auto'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : savedTheme;
            document.documentElement.dataset.bsTheme = resolvedTheme;
            document.documentElement.dataset.themePreference = savedTheme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="@yield('body-class')">
    <a class="skip-link" href="#main-content">{{ __('app.ui.skip_to_content') }}</a>

    @hasSection('auth-layout')
        <main id="main-content" class="auth-page">@yield('content')</main>
    @else
        <div class="app-layout">
            @auth
                <aside class="app-sidebar d-none d-lg-flex" aria-label="{{ __('app.ui.primary_navigation') }}">
                    @include('layouts.partials.sidebar')
                </aside>

                <div class="offcanvas offcanvas-end app-sidebar app-sidebar-mobile" tabindex="-1" id="mobile-navigation" aria-labelledby="mobile-navigation-label">
                    <div class="offcanvas-header border-bottom">
                        <h2 class="offcanvas-title h5" id="mobile-navigation-label">{{ __('app.name') }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="{{ __('app.ui.close') }}"></button>
                    </div>
                    <div class="offcanvas-body p-0">@include('layouts.partials.sidebar')</div>
                </div>
            @endauth

            <div class="app-main">
                <header class="app-topbar">
                    <div class="d-flex align-items-center gap-2">
                        @auth
                            <button class="btn btn-icon btn-ghost-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-navigation" aria-controls="mobile-navigation" aria-label="{{ __('app.ui.open_navigation') }}">
                                <x-admin.icon name="menu" />
                            </button>
                        @endauth
                        <a class="topbar-brand d-lg-none" href="{{ auth()->check() ? route('admin.dashboard') : route('home') }}">
                            <x-admin.brand-logo />
                            <span>{{ __('app.name') }}</span>
                        </a>
                        <div class="topbar-context d-none d-md-block">@yield('context', __('app.dashboard.eyebrow'))</div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        @auth
                            @can(\App\Enums\PermissionEnum::PROJECT_CREATE->value)
                                <a class="btn btn-aqar btn-sm d-none d-sm-inline-flex align-items-center gap-1" href="{{ route('admin.projects.create') }}">
                                    <x-admin.icon name="plus" size="17" />
                                    {{ __('app.projects.create') }}
                                </a>
                            @endcan
                        @endauth
                        <div class="dropdown">
                            <button class="btn btn-icon btn-ghost-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('app.ui.theme') }}">
                                <x-admin.icon name="sun" />
                            </button>
                            <div class="dropdown-menu dropdown-menu-end theme-menu">
                                @foreach (['light', 'dark', 'auto'] as $theme)
                                    <button class="dropdown-item d-flex align-items-center gap-2" type="button" data-theme-value="{{ $theme }}">
                                        <x-admin.icon :name="$theme === 'dark' ? 'moon' : 'sun'" size="17" />
                                        {{ __('app.ui.themes.'.$theme) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </header>

                <main id="main-content" class="app-content container-fluid">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show app-flash" role="status">
                            <x-admin.icon name="check" size="19" />
                            <span>{{ session('status') }}</span>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="{{ __('app.ui.close') }}"></button>
                        </div>
                    @endif
                    @yield('content')
                </main>

                <footer class="app-footer">
                    <span>{{ __('app.name') }}</span>
                    <span>{{ __('app.foundation.scope_note') }}</span>
                </footer>
            </div>
        </div>
    @endif

    <div class="modal fade" id="confirm-action-modal" tabindex="-1" aria-labelledby="confirm-action-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="confirm-action-title">{{ __('app.ui.confirm_title') }}</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('app.ui.close') }}"></button>
                </div>
                <div class="modal-body" id="confirm-action-message"></div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button class="btn btn-danger" id="confirm-action-submit" type="button">{{ __('app.ui.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
