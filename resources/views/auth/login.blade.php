@extends('layouts.admin')

@section('title', __('app.auth.login').' | '.__('app.name'))
@section('auth-layout', true)
@section('body-class', 'auth-body')

@section('content')
    <div class="login-layout">
        <section class="login-brand-panel" aria-label="{{ __('app.name') }}">
            <a class="login-brand text-white text-decoration-none" href="{{ route('home') }}">
                <x-admin.brand-logo />
                <span>{{ __('app.name') }}</span>
            </a>
            <div class="login-brand-copy">
                <p class="page-eyebrow text-white-50">{{ __('app.auth.workspace_eyebrow') }}</p>
                <h1>{{ __('app.auth.brand_title') }}</h1>
                <p>{{ __('app.auth.brand_description') }}</p>
                <div class="d-grid gap-3 mt-4">
                    <div class="login-brand-feature"><x-admin.icon name="chart" />{{ __('app.auth.feature_analysis') }}</div>
                    <div class="login-brand-feature"><x-admin.icon name="compare" />{{ __('app.auth.feature_comparison') }}</div>
                    <div class="login-brand-feature"><x-admin.icon name="lock" />{{ __('app.auth.feature_security') }}</div>
                </div>
            </div>
            <small class="text-white-50">{{ __('app.auth.brand_footer') }}</small>
        </section>

        <section class="login-form-panel">
            <div class="card login-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-4 d-lg-none">
                        <x-admin.brand-logo />
                        <strong>{{ __('app.name') }}</strong>
                    </div>
                    <p class="page-eyebrow">{{ __('app.auth.welcome_back') }}</p>
                    <h1 class="h2 mb-2">{{ __('app.auth.login') }}</h1>
                    <p class="text-secondary mb-4">{{ __('app.auth.login_description') }}</p>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <strong class="d-block mb-1">{{ __('app.ui.validation_title') }}</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">{{ __('app.users.email') }}</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><x-admin.icon name="mail" size="18" /></span>
                                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" dir="ltr">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">{{ __('app.users.password') }}</label>
                            <div class="input-group">
                                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required maxlength="255" autocomplete="current-password" dir="ltr">
                                <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="{{ __('app.auth.toggle_password') }}" aria-pressed="false">
                                    <x-admin.icon name="eye" size="18" />
                                </button>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                            <label class="form-check-label" for="remember">{{ __('app.auth.remember') }}</label>
                        </div>

                        <button class="btn btn-aqar btn-lg w-100" type="submit">{{ __('app.auth.login') }}</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
