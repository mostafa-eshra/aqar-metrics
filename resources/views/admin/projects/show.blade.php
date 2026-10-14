@extends('layouts.admin')

@section('title', __('app.projects.show').' | '.$project->name.' | '.__('app.name'))

@section('content')
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-3 mb-4">
        <div>
            <a class="small text-decoration-none" href="{{ route('admin.projects.index') }}">{{ __('app.projects.results.back_to_projects') }}</a>
            <p class="text-uppercase small fw-semibold text-secondary mt-3 mb-1">{{ __('app.projects.results.eyebrow') }}</p>
            <h1 class="h2 fw-bold text-aqar mb-2">{{ $project->name }}</h1>
            <div class="d-flex flex-wrap align-items-center gap-2 project-meta-list">
                <span class="badge {{ $dashboard['status_class'] }}">{{ $project->status->label() }}</span>
                <span>{{ __('app.projects.owner') }}: <strong>{{ $project->owner->name }}</strong></span>
                <span>{{ __('app.projects.currency') }}: <strong>{{ $dashboard['currency_label'] }} ({{ $project->currency_code }})</strong></span>
                <span>{{ __('app.projects.duration') }}: <strong>{{ trans_choice('app.projects.duration_years', $project->project_duration_years, ['count' => $project->project_duration_years]) }}</strong></span>
                @if ($project->location)
                    <span>{{ __('app.projects.results.location') }}: <strong>{{ $project->location }}</strong></span>
                @endif
                <span>{{ __('app.projects.last_updated') }}: <strong>{{ $dashboard['updated_at'] }}</strong></span>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('update', $project)
                <a class="btn btn-aqar" href="{{ route('admin.projects.edit', $project) }}">{{ __('app.edit') }}</a>
            @endcan
            @can('duplicate', $project)
                <form method="POST" action="{{ route('admin.projects.duplicate', $project) }}">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit">{{ __('app.projects.duplicate') }}</button>
                </form>
            @endcan
            @can('archive', $project)
                <form class="js-confirm-archive" method="POST" action="{{ route('admin.projects.archive', $project) }}" data-confirm="{{ __('app.projects.archive_confirmation') }}">
                    @csrf
                    <button class="btn btn-outline-danger" type="submit">{{ __('app.projects.archive') }}</button>
                </form>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($dashboard['warnings'] !== [])
        <section class="alert alert-warning" aria-labelledby="result-warnings-heading">
            <h2 class="h6 fw-bold" id="result-warnings-heading">{{ __('app.projects.results.warnings_title') }}</h2>
            <ul class="mb-0">
                @foreach ($dashboard['warnings'] as $warning)
                    <li>{{ $warning['message'] }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="alert bg-aqar-soft border" aria-labelledby="calculation-assumptions-heading">
        <h2 class="h6 fw-bold text-aqar" id="calculation-assumptions-heading">{{ __('app.projects.results.assumptions_title') }}</h2>
        <p class="mb-2">
            {{ __('app.projects.results.calculation_version') }}:
            <strong>{{ $dashboard['calculation_version_label'] }}</strong>
        </p>
        <ul class="mb-0">
            @foreach ($dashboard['calculation_notes'] as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </section>

    <nav class="project-results-tabs mb-4" aria-label="{{ __('app.projects.results.project_summary') }}">
        <div class="nav nav-pills flex-nowrap overflow-x-auto" id="project-results-tabs" role="tablist">
            <button class="nav-link active" id="project-summary-tab" data-bs-toggle="tab" data-bs-target="#project-summary-pane" type="button" role="tab" aria-controls="project-summary-pane" aria-selected="true">
                {{ __('app.projects.results.tabs.summary') }}
            </button>
            <button class="nav-link" id="project-inputs-tab" data-bs-toggle="tab" data-bs-target="#project-inputs-pane" type="button" role="tab" aria-controls="project-inputs-pane" aria-selected="false">
                {{ __('app.projects.results.tabs.inputs') }}
            </button>
            <button class="nav-link" id="project-revenues-tab" data-bs-toggle="tab" data-bs-target="#project-revenues-pane" type="button" role="tab" aria-controls="project-revenues-pane" aria-selected="false">
                {{ __('app.projects.results.tabs.revenues') }}
            </button>
            <button class="nav-link" id="project-costs-tab" data-bs-toggle="tab" data-bs-target="#project-costs-pane" type="button" role="tab" aria-controls="project-costs-pane" aria-selected="false">
                {{ __('app.projects.results.tabs.costs') }}
            </button>
            <button class="nav-link" id="project-calculation-tab" data-bs-toggle="tab" data-bs-target="#project-calculation-pane" type="button" role="tab" aria-controls="project-calculation-pane" aria-selected="false">
                {{ __('app.projects.results.tabs.calculation_details') }}
            </button>
        </div>
    </nav>

    <div class="tab-content" id="project-results-content">
        @include('admin.projects._summary_tab')
        @include('admin.projects._inputs_tab')
        @include('admin.projects._revenues_tab')
        @include('admin.projects._costs_tab')
        @include('admin.projects._calculation_details_tab')
    </div>
@endsection

@include('admin.projects._show_script')
