@extends('layouts.admin')

@section('title', __('app.auth.login').' | '.__('app.name'))

@section('content')
    <div class="row justify-content-center py-lg-5">
        <div class="col-md-8 col-lg-5">
            <section class="card app-card">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h3 fw-bold text-aqar mb-2">{{ __('app.auth.login') }}</h1>
                    <p class="text-secondary mb-4">{{ __('app.auth.login_description') }}</p>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="email">{{ __('app.users.email') }}</label>
                            <input
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                dir="ltr"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">{{ __('app.users.password') }}</label>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                type="password"
                                required
                                maxlength="255"
                                autocomplete="current-password"
                                dir="ltr"
                            >
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                            <label class="form-check-label" for="remember">{{ __('app.auth.remember') }}</label>
                        </div>

                        <button class="btn btn-aqar w-100" type="submit">{{ __('app.auth.login') }}</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
