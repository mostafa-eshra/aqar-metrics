<div class="tab-pane fade" id="project-calculation-pane" role="tabpanel" aria-labelledby="project-calculation-tab" tabindex="0">
    <section class="card app-card" aria-labelledby="calculation-details-heading">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h4 fw-bold text-aqar mb-1" id="calculation-details-heading">{{ __('app.projects.results.tabs.calculation_details') }}</h2>
                    <p class="text-secondary small mb-0">
                        {{ __('app.projects.results.calculation_version') }}:
                        <strong>{{ $dashboard['calculation_version_label'] }}</strong>
                    </p>
                </div>
                <div class="row g-2 calculation-filters">
                    <div class="col-sm-7">
                        <label class="visually-hidden" for="calculation-search">{{ __('app.projects.results.filter_details') }}</label>
                        <input class="form-control" id="calculation-search" type="search" placeholder="{{ __('app.projects.results.filter_placeholder') }}">
                    </div>
                    <div class="col-sm-5">
                        <label class="visually-hidden" for="calculation-type-filter">{{ __('app.projects.results.type') }}</label>
                        <select class="form-select" id="calculation-type-filter">
                            <option value="">{{ __('app.projects.results.all_types') }}</option>
                            <option value="input">{{ __('app.projects.results.types.input') }}</option>
                            <option value="computed">{{ __('app.projects.results.types.computed') }}</option>
                            <option value="assumption">{{ __('app.projects.results.types.assumption') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive results-table-scroll">
                <table class="table table-hover align-middle calculation-details-table mb-0" id="calculation-details-table">
                    <thead class="table-light">
                        <tr>
                            <th class="sticky-result-label" scope="col">{{ __('app.projects.results.metric') }}</th>
                            <th scope="col">{{ __('app.projects.results.value') }}</th>
                            <th scope="col">{{ __('app.projects.results.unit') }}</th>
                            <th scope="col">{{ __('app.projects.results.type') }}</th>
                            <th scope="col">{{ __('app.projects.results.formula') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['calculation_rows'] as $row)
                            <tr
                                id="metric-{{ $row['key'] }}"
                                data-calculation-row
                                data-calculation-type="{{ $row['type'] }}"
                                data-calculation-search="{{ Illuminate\Support\Str::lower($row['label'].' '.($row['formula'] ?? '')) }}"
                            >
                                <th class="sticky-result-label" scope="row">{{ $row['label'] }}</th>
                                <td>@include('admin.projects._result_value', ['value' => $row, 'showUnit' => false])</td>
                                <td>{{ $row['unit_label'] }}</td>
                                <td><span class="badge result-type-{{ $row['type'] }}">{{ $row['type_label'] }}</span></td>
                                <td class="calculation-formula">{{ $row['formula'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="alert alert-light border mt-3 mb-0 d-none" id="calculation-empty-state">{{ __('app.projects.results.no_matching_details') }}</p>
        </div>
    </section>
</div>
