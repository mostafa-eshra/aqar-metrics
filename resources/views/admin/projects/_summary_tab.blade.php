<div class="tab-pane fade show active" id="project-summary-pane" role="tabpanel" aria-labelledby="project-summary-tab" tabindex="0">
    <section aria-labelledby="summary-cards-heading">
        <h2 class="h4 fw-bold text-aqar mb-3" id="summary-cards-heading">{{ __('app.projects.results.summary_cards') }}</h2>
        <div class="row g-3 mb-4">
            @foreach ($dashboard['summary_cards'] as $card)
                <div class="col-sm-6 col-xl-4">
                    <article class="result-card h-100">
                        <span class="result-card-label">{{ $card['label'] }}</span>
                        <div class="result-card-value">
                            @include('admin.projects._result_value', ['value' => $card])
                        </div>
                        <button class="btn btn-link btn-sm p-0 mt-2 text-decoration-none" type="button" data-metric-target="metric-{{ $card['key'] }}">
                            {{ __('app.projects.results.view_calculation') }}
                        </button>
                    </article>
                </div>
            @endforeach
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="card app-card h-100" aria-labelledby="annual-chart-heading">
                <div class="card-body">
                    <h2 class="h4 fw-bold text-aqar mb-1" id="annual-chart-heading">{{ __('app.projects.results.annual_collection_chart') }}</h2>
                    <p class="text-secondary small mb-4">{{ __('app.projects.results.annual_collection_chart_help') }}</p>

                    @if ($dashboard['annual_collection_chart']['available'])
                        <div class="results-chart-scroll">
                            <div class="annual-chart" role="img" aria-label="{{ __('app.projects.results.annual_collection_chart') }}">
                                @foreach ($dashboard['annual_collection_chart']['rows'] as $year)
                                    <div class="annual-chart-column" data-collection-year="{{ $year['year'] }}">
                                        <span class="annual-chart-total" dir="ltr">{{ $year['formatted_total'] }}</span>
                                        <div class="annual-chart-track">
                                            <div class="annual-chart-stack" style="--bar-height: {{ $year['height_percent'] }}%">
                                                @foreach ($year['segments'] as $segment)
                                                    @if ($segment['share_percent'] !== '0.00')
                                                        <span
                                                            class="chart-segment chart-color-{{ $loop->index }}"
                                                            style="--segment-size: {{ $segment['share_percent'] }}%"
                                                            title="{{ $segment['label'] }}: {{ $segment['formatted_value'] }} {{ $dashboard['currency_symbol'] }}"
                                                            aria-label="{{ $segment['label'] }}: {{ $segment['formatted_value'] }} {{ $dashboard['currency_symbol'] }}"
                                                        ></span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        <span class="annual-chart-label">{{ __('app.projects.collection_schedule.year', ['year' => $year['year']]) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="chart-legend mt-3">
                            @foreach ($dashboard['annual_collection_chart']['rows'][0]['segments'] as $segment)
                                <span><i class="chart-color-{{ $loop->index }}" aria-hidden="true"></i>{{ $segment['label'] }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">{{ __('app.projects.results.chart_unavailable') }}</div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="card app-card h-100" aria-labelledby="cost-chart-heading">
                <div class="card-body">
                    <h2 class="h4 fw-bold text-aqar mb-1" id="cost-chart-heading">{{ __('app.projects.results.cost_composition_chart') }}</h2>
                    <p class="text-secondary small mb-4">{{ __('app.projects.results.cost_composition_chart_help') }}</p>

                    @if ($dashboard['cost_chart']['available'])
                        <div class="cost-chart" role="img" aria-label="{{ __('app.projects.results.cost_composition_chart') }}">
                            @foreach ($dashboard['cost_chart']['items'] as $item)
                                @if ($item['share_percent'] !== '0.00')
                                    <span
                                        class="chart-segment chart-color-{{ $loop->index % 10 }}"
                                        style="--segment-size: {{ $item['share_percent'] }}%"
                                        title="{{ $item['label'] }}: {{ $item['formatted_value'] }} {{ $dashboard['currency_symbol'] }}"
                                        aria-label="{{ $item['label'] }}: {{ $item['formatted_value'] }} {{ $dashboard['currency_symbol'] }}"
                                    ></span>
                                @endif
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between gap-3 mt-2 fw-semibold">
                            <span>{{ __('app.projects.results.chart_total') }}</span>
                            <span><span dir="ltr">{{ $dashboard['cost_chart']['total']['formatted_value'] }}</span> {{ $dashboard['currency_symbol'] }}</span>
                        </div>
                        <p class="small mt-2 mb-3 {{ $dashboard['cost_chart_reconciles'] ? 'text-success' : 'text-danger' }}">
                            {{ $dashboard['cost_chart_reconciles'] ? __('app.projects.results.chart_reconciled') : __('app.projects.results.chart_not_reconciled') }}
                        </p>
                        <div class="cost-legend">
                            @foreach ($dashboard['cost_chart']['items'] as $item)
                                <button class="cost-legend-item" type="button" data-metric-target="metric-{{ $item['key'] }}">
                                    <i class="chart-color-{{ $loop->index % 10 }}" aria-hidden="true"></i>
                                    <span>{{ $item['label'] }}</span>
                                    <strong dir="ltr">{{ $item['formatted_value'] }}</strong>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">{{ __('app.projects.results.chart_unavailable') }}</div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="card app-card" aria-labelledby="phase-comparison-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-1" id="phase-comparison-heading">{{ __('app.projects.results.phase_comparison') }}</h2>
            <p class="text-secondary small mb-3">{{ __('app.projects.results.phase_comparison_help') }}</p>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('app.projects.results.metric') }}</th>
                            <th scope="col">{{ __('app.projects.results.quantity') }}</th>
                            <th scope="col">{{ __('app.projects.results.rate') }}</th>
                            <th scope="col">{{ __('app.projects.results.amount') }}</th>
                            <th scope="col">{{ __('app.projects.results.percentage') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['phase_comparison'] as $phase)
                            <tr>
                                <th scope="row">{{ $phase['label'] }}</th>
                                <td>@include('admin.projects._result_value', ['value' => $phase['area']])</td>
                                <td>@include('admin.projects._result_value', ['value' => $phase['price']])</td>
                                <td>@include('admin.projects._result_value', ['value' => $phase['revenue']])</td>
                                <td class="phase-share-cell">
                                    @include('admin.projects._result_value', ['value' => $phase['contribution']])
                                    @if ($phase['contribution']['is_available'])
                                        <span class="phase-share-track" aria-hidden="true">
                                            <span style="--phase-share: {{ $phase['contribution']['raw_value'] }}%"></span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
