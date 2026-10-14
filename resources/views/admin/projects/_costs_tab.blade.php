<div class="tab-pane fade" id="project-costs-pane" role="tabpanel" aria-labelledby="project-costs-tab" tabindex="0">
    <section class="card app-card mb-4" aria-labelledby="construction-costs-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-3" id="construction-costs-heading">{{ __('app.projects.results.construction_costs') }}</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('app.projects.results.metric') }}</th>
                            <th scope="col">{{ __('app.projects.results.quantity') }}</th>
                            <th scope="col">{{ __('app.projects.results.rate') }}</th>
                            <th scope="col">{{ __('app.projects.results.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['construction_rows'] as $row)
                            <tr>
                                <th scope="row">{{ $row['label'] }}</th>
                                <td>@include('admin.projects._result_value', ['value' => $row['area']])</td>
                                <td>@include('admin.projects._result_value', ['value' => $row['rate']])</td>
                                <td>
                                    <button class="btn btn-link p-0 text-decoration-none result-number" type="button" data-metric-target="metric-{{ $row['metric_key'] }}">
                                        @include('admin.projects._result_value', ['value' => $row['total']])
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <th scope="row" colspan="3">{{ __('app.projects.results.metrics.construction_cost_total') }}</th>
                            <td>
                                @php($constructionTotal = collect($dashboard['calculation_rows'])->firstWhere('key', 'construction_cost_total'))
                                @include('admin.projects._result_value', ['value' => $constructionTotal])
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card app-card mb-4" aria-labelledby="expenses-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-3" id="expenses-heading">{{ __('app.projects.results.expenses') }}</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('app.projects.results.metric') }}</th>
                            <th scope="col">{{ __('app.projects.results.percentage') }}</th>
                            <th scope="col">{{ __('app.projects.results.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['expense_rows'] as $row)
                            <tr>
                                <th scope="row">{{ $row['label'] }}</th>
                                <td>
                                    @if ($row['percent'] !== null)
                                        @include('admin.projects._result_value', ['value' => $row['percent']])
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                <td>@include('admin.projects._result_value', ['value' => $row['amount']])</td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <th scope="row" colspan="2">{{ __('app.projects.results.metrics.expense_total_including_land') }}</th>
                            <td>
                                @php($expenseTotal = collect($dashboard['calculation_rows'])->firstWhere('key', 'expense_total_including_land'))
                                @include('admin.projects._result_value', ['value' => $expenseTotal])
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card app-card" aria-labelledby="grand-totals-heading">
        <div class="card-body">
            <h2 class="h4 fw-bold text-aqar mb-3" id="grand-totals-heading">{{ __('app.projects.results.grand_totals') }}</h2>
            <div class="row g-3">
                @foreach (['total_cost', 'estimated_profit'] as $metricKey)
                    @php($metric = collect($dashboard['calculation_rows'])->firstWhere('key', $metricKey))
                    <div class="col-md-6">
                        <div class="result-card h-100">
                            <span class="result-card-label">{{ $metric['label'] }}</span>
                            <div class="result-card-value">@include('admin.projects._result_value', ['value' => $metric])</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
