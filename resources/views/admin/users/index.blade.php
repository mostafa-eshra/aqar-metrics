@extends('layouts.admin')

@section('title', __('app.users.title').' | '.__('app.name'))

@section('content')
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold text-aqar mb-1">{{ __('app.users.title') }}</h1>
            <p class="text-secondary mb-0">{{ __('app.users.index_description') }}</p>
        </div>

        @can(\App\Enums\PermissionEnum::USER_CREATE->value)
            <a class="btn btn-aqar" href="{{ route('admin.users.create') }}">
                {{ __('app.users.create') }}
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="card app-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('app.users.name') }}</th>
                        <th scope="col">{{ __('app.users.email') }}</th>
                        <th scope="col">{{ __('app.users.role') }}</th>
                        <th scope="col">{{ __('app.users.status') }}</th>
                        <th scope="col" class="text-nowrap">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td dir="ltr" class="text-end">{{ $user->email }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $user->is_active ? __('app.users.active') : __('app.users.inactive') }}
                                </span>
                            </td>
                            <td>
                                @can(\App\Enums\PermissionEnum::USER_EDIT->value)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.edit', $user) }}">
                                        {{ __('app.edit') }}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">{{ __('app.users.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $users->links() }}
            </div>
        @endif
    </section>
@endsection
