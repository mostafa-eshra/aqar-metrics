<div class="tab-pane fade" id="project-revenues-pane" role="tabpanel" aria-labelledby="project-revenues-tab" tabindex="0">
    <section class="card app-card mb-4" aria-labelledby="revenue-totals-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-3" id="revenue-totals-heading">{{ __('app.projects.results.revenue_totals') }}</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('app.projects.results.metric') }}</th>
                            <th scope="col">{{ __('app.projects.results.value') }}</th>
                            <th scope="col">{{ __('app.projects.results.unit') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['revenue_rows'] as $row)
                            <tr>
                                <th scope="row">
                                    <button class="btn btn-link p-0 text-start text-decoration-none" type="button" data-metric-target="metric-{{ $row['key'] }}">
                                        {{ $row['label'] }}
                                    </button>
                                </th>
                                <td>@include('admin.projects._result_value', ['value' => $row, 'showUnit' => false])</td>
                                <td>{{ $row['unit_label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card app-card" aria-labelledby="annual-collection-table-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-1" id="annual-collection-table-heading">{{ __('app.projects.results.annual_collection_table') }}</h2>
            <p class="text-secondary small mb-3">{{ __('app.projects.results.annual_collection_table_help') }}</p>
            <div class="table-responsive results-table-scroll">
                <table class="table table-hover align-middle annual-results-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="sticky-result-label" scope="col">{{ __('app.projects.preview.year') }}</th>
                            @foreach ($dashboard['schedule_rows'] as $scheduleRow)
                                <th scope="col">{{ $scheduleRow['label'] }}</th>
                            @endforeach
                            <th scope="col">{{ __('app.projects.preview.year_collection') }}</th>
                            <th scope="col">{{ __('app.projects.preview.cumulative_collection') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboard['annual_collections'] as $year)
                            <tr data-annual-result-year="{{ $year['year'] }}">
                                <th class="sticky-result-label" scope="row">{{ __('app.projects.collection_schedule.year', ['year' => $year['year']]) }}</th>
                                @foreach ($dashboard['schedule_rows'] as $scheduleRow)
                                    @php($groupValue = $year['groups'][$scheduleRow['group']])
                                    <td>
                                        @if ($groupValue['raw_value'] !== null)
                                            <span dir="ltr">{{ $groupValue['formatted_value'] }}</span>
                                        @else
                                            <span class="text-danger">{{ __('app.projects.results.unavailable') }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="fw-semibold">@include('admin.projects._result_value', ['value' => $year['total'], 'showUnit' => false])</td>
                                <td class="fw-semibold">@include('admin.projects._result_value', ['value' => $year['cumulative_total'], 'showUnit' => false])</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-secondary py-4" colspan="8">{{ __('app.projects.results.chart_unavailable') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <th class="sticky-result-label" scope="row" colspan="{{ count($dashboard['schedule_rows']) + 1 }}">{{ __('app.projects.preview.collection_total') }}</th>
                            <td colspan="2">
                                @include('admin.projects._result_value', ['value' => $dashboard['annual_collection_total']])
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>
</div>
