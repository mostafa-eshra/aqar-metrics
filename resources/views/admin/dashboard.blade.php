@extends('layouts.admin')

@section('title', __('app.dashboard.title').' | '.__('app.name'))
@section('context', __('app.dashboard.title'))

@section('content')
    <x-admin.page-header
        :eyebrow="__('app.dashboard.eyebrow')"
        :title="__('app.dashboard.welcome', ['name' => auth()->user()->name])"
        :description="__('app.dashboard.description')"
    >
        <x-slot:actions>
            @can(\App\Enums\PermissionEnum::PROJECT_CREATE->value)
                <a class="btn btn-aqar d-inline-flex align-items-center gap-2" href="{{ route('admin.projects.create') }}">
                    <x-admin.icon name="plus" size="18" />
                    {{ __('app.projects.create') }}
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <section class="row g-3 mb-4" aria-label="{{ __('app.dashboard.metrics') }}">
        <div class="col-6 col-xl-3">
            <x-admin.kpi-card :label="__('app.dashboard.total_projects')" :value="$dashboard['total']" icon="building" />
        </div>
        <div class="col-6 col-xl-3">
            <x-admin.kpi-card :label="__('app.dashboard.drafts')" :value="$dashboard['drafts']" icon="edit" tone="warning" />
        </div>
        <div class="col-6 col-xl-3">
            <x-admin.kpi-card :label="__('app.dashboard.completed')" :value="$dashboard['completed']" icon="check" tone="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-admin.kpi-card :label="__('app.dashboard.recently_updated')" :value="$dashboard['recently_updated']" icon="clock" :hint="__('app.dashboard.last_seven_days')" />
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="card app-card h-100" aria-labelledby="recent-projects-heading">
                <div class="card-header">
                    <div>
                        <h2 class="card-title" id="recent-projects-heading">{{ __('app.dashboard.recent_projects') }}</h2>
                        <p class="card-subtitle">{{ __('app.dashboard.recent_projects_description') }}</p>
                    </div>
                    @can(\App\Enums\PermissionEnum::PROJECT_LIST->value)
                        <a class="btn btn-sm btn-ghost-primary" href="{{ route('admin.projects.index') }}">{{ __('app.dashboard.view_all') }}</a>
                    @endcan
                </div>

                @forelse ($dashboard['recent_projects'] as $project)
                    <a class="recent-project" href="{{ route('admin.projects.show', $project) }}">
                        <span class="recent-project-name">
                            <strong>{{ $project->name }}</strong>
                            <small>{{ $project->location ?: __('app.projects.not_available') }}</small>
                        </span>
                        <span class="recent-project-meta">{{ __('app.projects.owner') }}<br><strong>{{ $project->owner->name }}</strong></span>
                        <span class="badge {{ $project->status === \App\Enums\ProjectStatus::COMPLETED ? 'bg-success-lt text-success' : ($project->status === \App\Enums\ProjectStatus::DRAFT ? 'bg-yellow-lt text-yellow' : 'bg-secondary-lt text-secondary') }}">
                            <span class="status-dot"></span>{{ $project->status->label() }}
                        </span>
                        <span class="recent-project-meta text-nowrap" dir="ltr">{{ $project->updated_at?->diffForHumans() }}</span>
                    </a>
                @empty
                    <x-admin.empty-state :title="__('app.dashboard.empty_title')" :description="__('app.dashboard.empty_description')">
                        <x-slot:action>
                            @can(\App\Enums\PermissionEnum::PROJECT_CREATE->value)
                                <a class="btn btn-aqar" href="{{ route('admin.projects.create') }}">{{ __('app.projects.create') }}</a>
                            @endcan
                        </x-slot:action>
                    </x-admin.empty-state>
                @endforelse
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card app-card h-100" aria-labelledby="quick-actions-heading">
                <div class="card-header">
                    <h2 class="card-title" id="quick-actions-heading">{{ __('app.dashboard.quick_actions') }}</h2>
                </div>
                <div class="list-group list-group-flush">
                    @can(\App\Enums\PermissionEnum::PROJECT_LIST->value)
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="{{ route('admin.projects.index') }}">
                            <span class="kpi-icon"><x-admin.icon name="building" /></span>
                            <span><strong class="d-block">{{ __('app.projects.manage') }}</strong><small class="text-secondary">{{ __('app.dashboard.projects_action_help') }}</small></span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionEnum::PROJECT_COMPARE->value)
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="{{ route('admin.projects.compare') }}">
                            <span class="kpi-icon"><x-admin.icon name="compare" /></span>
                            <span><strong class="d-block">{{ __('app.phase_nine.compare') }}</strong><small class="text-secondary">{{ __('app.dashboard.compare_action_help') }}</small></span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionEnum::USER_LIST->value)
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="{{ route('admin.users.index') }}">
                            <span class="kpi-icon"><x-admin.icon name="users" /></span>
                            <span><strong class="d-block">{{ __('app.users.manage') }}</strong><small class="text-secondary">{{ __('app.dashboard.users_action_help') }}</small></span>
                        </a>
                    @endcan
                </div>
            </section>
        </div>
    </div>
@endsection
