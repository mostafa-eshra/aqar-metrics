<div class="sidebar-content">
    <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
        <x-admin.brand-logo />
        <span class="sidebar-brand-copy">
            <strong>{{ __('app.name') }}</strong>
            <small>{{ __('app.ui.workspace') }}</small>
        </span>
    </a>

    <nav class="sidebar-nav" aria-label="{{ __('app.ui.primary_navigation') }}">
        <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            <x-admin.icon name="dashboard" />
            <span>{{ __('app.dashboard.title') }}</span>
        </a>
        @can(\App\Enums\PermissionEnum::PROJECT_LIST->value)
            <a class="sidebar-link {{ request()->routeIs('admin.projects.index', 'admin.projects.create', 'admin.projects.edit', 'admin.projects.show', 'admin.projects.analysis') ? 'active' : '' }}" href="{{ route('admin.projects.index') }}" @if(request()->routeIs('admin.projects.index', 'admin.projects.create', 'admin.projects.edit', 'admin.projects.show', 'admin.projects.analysis')) aria-current="page" @endif>
                <x-admin.icon name="building" />
                <span>{{ __('app.projects.title') }}</span>
            </a>
        @endcan
        @can(\App\Enums\PermissionEnum::PROJECT_COMPARE->value)
            <a class="sidebar-link {{ request()->routeIs('admin.projects.compare') ? 'active' : '' }}" href="{{ route('admin.projects.compare') }}" @if(request()->routeIs('admin.projects.compare')) aria-current="page" @endif>
                <x-admin.icon name="compare" />
                <span>{{ __('app.phase_nine.compare') }}</span>
            </a>
        @endcan
        @can(\App\Enums\PermissionEnum::USER_LIST->value)
            <a class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                <x-admin.icon name="users" />
                <span>{{ __('app.users.title') }}</span>
            </a>
        @endcan
    </nav>

    <div class="sidebar-user">
        <span class="avatar avatar-sm">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
        <span class="sidebar-user-copy">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->role->label() }}</small>
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-icon btn-ghost-light" type="submit" aria-label="{{ __('app.auth.logout') }}" title="{{ __('app.auth.logout') }}">
                <x-admin.icon name="logout" size="18" />
            </button>
        </form>
    </div>
</div>
