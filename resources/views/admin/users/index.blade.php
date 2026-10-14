@extends('layouts.admin')

@section('title', __('app.users.title').' | '.__('app.name'))
@section('context', __('app.users.title'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.users.eyebrow')" :title="__('app.users.title')" :description="__('app.users.index_description')">
        <x-slot:actions>
            @can(\App\Enums\PermissionEnum::USER_CREATE->value)
                <a class="btn btn-aqar d-inline-flex align-items-center gap-2" href="{{ route('admin.users.create') }}">
                    <x-admin.icon name="plus" size="18" />{{ __('app.users.create') }}
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <section class="card app-card overflow-hidden" aria-labelledby="users-table-heading">
        <div class="card-header">
            <div>
                <h2 class="card-title" id="users-table-heading">{{ __('app.users.accounts') }}</h2>
                <p class="card-subtitle">{{ __('app.users.accounts_description') }}</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">{{ __('app.users.name') }}</th>
                        <th scope="col">{{ __('app.users.email') }}</th>
                        <th scope="col">{{ __('app.users.role') }}</th>
                        <th scope="col">{{ __('app.users.status') }}</th>
                        <th class="text-end" scope="col">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar-initials" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                                    <strong>{{ $user->name }}</strong>
                                </div>
                            </td>
                            <td dir="ltr" class="text-end">{{ $user->email }}</td>
                            <td><span class="badge bg-azure-lt text-azure">{{ $user->role->label() }}</span></td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">
                                    <span class="status-dot"></span>{{ $user->is_active ? __('app.users.active') : __('app.users.inactive') }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can(\App\Enums\PermissionEnum::USER_EDIT->value)
                                    <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" href="{{ route('admin.users.edit', $user) }}">
                                        <x-admin.icon name="edit" size="16" />{{ __('app.edit') }}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state :title="__('app.users.empty')" :description="__('app.users.empty_description')" icon="users" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
