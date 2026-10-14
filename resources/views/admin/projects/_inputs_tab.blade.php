<div class="tab-pane fade" id="project-inputs-pane" role="tabpanel" aria-labelledby="project-inputs-tab" tabindex="0">
    <section class="card app-card mb-4" aria-labelledby="input-values-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-1" id="input-values-heading">{{ __('app.projects.results.input_values') }}</h2>
            <p class="text-secondary small mb-3">{{ __('app.projects.results.input_values_help') }}</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('app.projects.results.metric') }}</th>
                            <th scope="col">{{ __('app.projects.results.value') }}</th>
                            <th scope="col">{{ __('app.projects.results.unit') }}</th>
                            <th scope="col">{{ __('app.projects.results.type') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['input_rows'] as $row)
                            <tr>
                                <th scope="row">{{ $row['label'] }}</th>
                                <td>
                                    @include('admin.projects._result_value', ['value' => $row, 'showUnit' => false])
                                </td>
                                <td>{{ $row['unit_label'] }}</td>
                                <td><span class="badge result-type-{{ $row['type'] }}">{{ $row['type_label'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card app-card" aria-labelledby="saved-schedule-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-3" id="saved-schedule-heading">{{ __('app.projects.results.collection_schedule') }}</h2>
            <div class="table-responsive results-table-scroll">
                <table class="table table-hover align-middle collection-results-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="sticky-result-label" scope="col">{{ __('app.projects.collection_schedule.group') }}</th>
                            @foreach ($dashboard['schedule_years'] as $year)
                                <th scope="col">
                                    {{ __('app.projects.collection_schedule.year', ['year' => $year]) }}
                                    @if ($year > $project->project_duration_years)
                                        <span class="badge text-bg-warning d-block mt-1">{{ __('app.projects.results.outside_duration') }}</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['schedule_rows'] as $row)
                            <tr>
                                <th class="sticky-result-label" scope="row">{{ $row['label'] }}</th>
                                @foreach ($dashboard['schedule_years'] as $year)
                                    <td class="{{ $row['values']["year_{$year}"]['is_outside_duration'] ? 'table-warning' : '' }}">
                                        @if ($row['values']["year_{$year}"]['raw_value'] !== null)
                                            <span dir="ltr">{{ $row['values']["year_{$year}"]['formatted_value'] }}%</span>
                                        @else
                                            <span class="text-danger">{{ __('app.projects.not_available') }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
