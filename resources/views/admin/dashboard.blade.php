@extends('layouts.admin')

@section('title', __('app.dashboard.title').' | '.__('app.name'))

@section('content')
    <section class="card app-card">
        <div class="card-body p-4 p-lg-5">
            <span class="badge rounded-pill bg-aqar-soft text-aqar mb-3 px-3 py-2">
                {{ __('app.dashboard.eyebrow') }}
            </span>
            <h1 class="h2 fw-bold text-aqar mb-2">{{ __('app.dashboard.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p class="text-secondary mb-4">
                {{ __('app.dashboard.role') }}: {{ auth()->user()->role->label() }}
            </p>

            @can(\App\Enums\PermissionEnum::USER_LIST->value)
                <a class="btn btn-aqar" href="{{ route('admin.users.index') }}">
                    {{ __('app.users.manage') }}
                </a>
            @endcan
        </div>
    </section>
@endsection
