@extends('layouts.admin')

@section('title', __('app.projects.title').' | '.__('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold text-aqar mb-1">{{ __('app.projects.title') }}</h1>
            <p class="text-secondary mb-0">{{ __('app.projects.index_description') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can(\App\Enums\PermissionEnum::PROJECT_COMPARE->value)
                <a class="btn btn-outline-primary" href="{{ route('admin.projects.compare') }}">{{ __('app.phase_nine.compare') }}</a>
            @endcan
            @can(\App\Enums\PermissionEnum::PROJECT_CREATE->value)
                <a class="btn btn-aqar" href="{{ route('admin.projects.create') }}">{{ __('app.projects.create') }}</a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="card app-card mb-4">
        <div class="card-body">
            <form id="project-filter-form" class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label" for="filter_search">{{ __('app.projects.search') }}</label>
                    <input class="form-control" id="filter_search" name="filter_search" type="search" placeholder="{{ __('app.projects.search_placeholder') }}">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="filter_status">{{ __('app.projects.filter_status') }}</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">{{ __('app.projects.all_statuses') }}</option>
                        @foreach (\App\Enums\ProjectStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-aqar flex-grow-1" type="submit">{{ __('app.projects.apply_filters') }}</button>
                    <button class="btn btn-outline-secondary" id="reset-project-filters" type="button" aria-label="{{ __('app.projects.reset_filters') }}">↻</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card app-card overflow-hidden">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle w-100 mb-0" id="projects-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('app.projects.attributes.name') }}</th>
                        <th scope="col">{{ __('app.projects.owner') }}</th>
                        <th scope="col">{{ __('app.projects.attributes.status') }}</th>
                        <th scope="col">{{ __('app.projects.currency') }}</th>
                        <th scope="col">{{ __('app.projects.duration') }}</th>
                        <th scope="col">{{ __('app.projects.last_updated') }}</th>
                        <th scope="col">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.getElementById('project-filter-form');
            const resetButton = document.getElementById('reset-project-filters');
            const tableElement = document.getElementById('projects-table');

            if (!filterForm || !resetButton || !tableElement || !window.jQuery?.fn?.DataTable) {
                return;
            }

            const table = window.jQuery(tableElement).DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 10,
                order: [[5, 'desc']],
                ajax: {
                    url: @json(route('admin.projects.index')),
                    data: data => {
                        data.filter_search = document.getElementById('filter_search').value;
                        data.filter_status = document.getElementById('filter_status').value;
                    },
                },
                columns: [
                    { data: 'name', name: 'name', searchable: false },
                    { data: 'owner_name', name: 'owner_name', searchable: false },
                    { data: 'status', name: 'status', searchable: false },
                    { data: 'currency_code', name: 'currency_code', searchable: false },
                    { data: 'project_duration_years', name: 'project_duration_years', searchable: false },
                    { data: 'updated_at', name: 'updated_at', searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                language: {
                    processing: @json(__('app.projects.datatable.processing')),
                    emptyTable: @json(__('app.projects.datatable.empty_table')),
                    zeroRecords: @json(__('app.projects.empty')),
                    info: @json(__('app.projects.datatable.info')),
                    infoEmpty: @json(__('app.projects.datatable.info_empty')),
                    lengthMenu: @json(__('app.projects.datatable.length_menu')),
                    paginate: {
                        next: @json(__('app.projects.datatable.paginate_next')),
                        previous: @json(__('app.projects.datatable.paginate_previous')),
                    },
                },
            });

            filterForm.addEventListener('submit', event => {
                event.preventDefault();
                table.draw();
            });

            resetButton.addEventListener('click', () => {
                filterForm.reset();
                table.draw();
            });

            tableElement.addEventListener('submit', event => {
                const form = event.target.closest('.js-confirm-archive');

                if (form && !window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
