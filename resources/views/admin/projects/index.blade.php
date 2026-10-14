@extends('layouts.admin')

@section('title', __('app.projects.title').' | '.__('app.name'))
@section('context', __('app.projects.title'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.dashboard.eyebrow')" :title="__('app.projects.title')" :description="__('app.projects.index_description')">
        <x-slot:actions>
            @can(\App\Enums\PermissionEnum::PROJECT_COMPARE->value)
                <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('admin.projects.compare') }}">
                    <x-admin.icon name="compare" size="18" />{{ __('app.phase_nine.compare') }}
                </a>
            @endcan
            @can(\App\Enums\PermissionEnum::PROJECT_CREATE->value)
                <a class="btn btn-aqar d-inline-flex align-items-center gap-2" href="{{ route('admin.projects.create') }}">
                    <x-admin.icon name="plus" size="18" />{{ __('app.projects.create') }}
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <section class="card app-card filter-panel mb-3" aria-labelledby="project-filters-heading">
        <div class="card-header d-lg-none">
            <button class="btn btn-ghost-secondary w-100 d-flex align-items-center justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#project-filters" aria-expanded="false" aria-controls="project-filters">
                <span class="d-flex align-items-center gap-2"><x-admin.icon name="filter" size="18" />{{ __('app.projects.filters') }}</span>
                <x-admin.icon name="chevron-left" size="18" />
            </button>
        </div>
        <div class="collapse d-lg-block" id="project-filters">
            <div class="card-body">
                <h2 class="visually-hidden" id="project-filters-heading">{{ __('app.projects.filters') }}</h2>
                <form id="project-filter-form" class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label" for="filter_search">{{ __('app.projects.search') }}</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><x-admin.icon name="search" size="18" /></span>
                            <input class="form-control" id="filter_search" name="filter_search" type="search" placeholder="{{ __('app.projects.search_placeholder') }}">
                        </div>
                    </div>
                    <div class="col-sm-7 col-lg-3">
                        <label class="form-label" for="filter_status">{{ __('app.projects.filter_status') }}</label>
                        <select class="form-select" id="filter_status" name="filter_status">
                            <option value="">{{ __('app.projects.all_statuses') }}</option>
                            @foreach (\App\Enums\ProjectStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-5 col-lg-2 d-flex gap-2">
                        <button class="btn btn-aqar flex-grow-1" type="submit">{{ __('app.projects.apply_filters') }}</button>
                        <button class="btn btn-icon btn-outline-secondary" id="reset-project-filters" type="button" aria-label="{{ __('app.projects.reset_filters') }}" title="{{ __('app.projects.reset_filters') }}">
                            <x-admin.icon name="refresh" size="18" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @php
        $dataTableLabels = [
            'processing' => __('app.projects.datatable.processing'),
            'emptyTable' => __('app.projects.datatable.empty_table'),
            'zeroRecords' => __('app.projects.empty'),
            'info' => __('app.projects.datatable.info'),
            'infoEmpty' => __('app.projects.datatable.info_empty'),
            'lengthMenu' => __('app.projects.datatable.length_menu'),
            'paginate' => [
                'next' => __('app.projects.datatable.paginate_next'),
                'previous' => __('app.projects.datatable.paginate_previous'),
            ],
        ];
    @endphp

    <section class="card app-card data-table-card position-relative" data-project-table-card aria-labelledby="projects-table-heading">
        <div class="card-header">
            <div>
                <h2 class="card-title" id="projects-table-heading">{{ __('app.projects.table_title') }}</h2>
                <p class="card-subtitle">{{ __('app.projects.table_description') }}</p>
            </div>
        </div>
        <div class="table-loading" role="status">
            <span class="spinner-border text-primary" aria-hidden="true"></span>
            <span class="visually-hidden">{{ __('app.projects.datatable.processing') }}</span>
        </div>
        <div class="table-responsive p-3">
            <table
                class="table table-vcenter table-hover w-100 mb-0"
                id="projects-table"
                data-url="{{ route('admin.projects.index') }}"
                data-labels='@json($dataTableLabels)'
            >
                <thead>
                    <tr>
                        <th scope="col">{{ __('app.projects.attributes.name') }}</th>
                        <th scope="col">{{ __('app.projects.owner') }}</th>
                        <th scope="col">{{ __('app.projects.attributes.status') }}</th>
                        <th scope="col">{{ __('app.projects.currency') }}</th>
                        <th scope="col">{{ __('app.projects.duration') }}</th>
                        <th scope="col">{{ __('app.projects.last_updated') }}</th>
                        <th class="text-end" scope="col">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/projects-index.js')
@endpush
