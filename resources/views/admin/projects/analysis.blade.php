@extends('layouts.admin')

@section('title', __('app.phase_nine.title').' | '.$project->name.' | '.__('app.name'))
@section('context', __('app.phase_nine.title'))

@section('content')
    <x-admin.page-header :eyebrow="$project->name" :title="__('app.phase_nine.title')" :description="__('app.phase_nine.description')">
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.projects.show', $project) }}">{{ __('app.phase_nine.back_to_project') }}</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.projects.exports.excel', $project) }}">{{ __('app.phase_nine.export_excel') }}</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.projects.compare') }}">{{ __('app.phase_nine.compare') }}</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4 analysis-grid">
        <div class="col-12">
            <section class="card app-card">
                <div class="card-body">
                    <h2 class="h5 text-aqar">{{ __('app.phase_nine.cash_flow.title') }}</h2>
                    @if ($cashFlow->annualRows === [])
                        <div class="alert alert-warning mb-0">{{ __('app.phase_nine.cash_flow.unavailable') }}</div>
                    @else
                        <div class="row g-3 mb-4">
                            @foreach ([
                                ['label' => __('app.phase_nine.cash_flow.npv'), 'value' => $cashFlow->netPresentValue, 'suffix' => ' '.$project->currency_code],
                                ['label' => __('app.phase_nine.cash_flow.irr'), 'value' => $cashFlow->internalRateOfReturnPercent, 'suffix' => '%'],
                                ['label' => __('app.phase_nine.cash_flow.payback'), 'value' => $cashFlow->paybackPeriodYears, 'suffix' => ''],
                            ] as $indicator)
                                <div class="col-md-4">
                                    <div class="result-card h-100">
                                        <span class="result-card-label">{{ $indicator['label'] }}</span>
                                        <strong class="result-card-value">
                                            {{ $indicator['value'] === null ? __('app.projects.not_available') : number_format((float) $indicator['value'], 2).$indicator['suffix'] }}
                                        </strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($cashFlow->unavailableReasons !== [])
                            <div class="alert alert-warning">{{ __('app.phase_nine.cash_flow.unavailable') }}</div>
                        @endif
                        <div class="table-responsive border rounded-3">
                            <table class="table table-sm align-middle text-end mb-0 cash-flow-results-table">
                                <thead class="table-light">
                                    <tr>
                                        @foreach (['year', 'collections', 'other_revenue', 'project_cost', 'financing_inflow', 'financing_cost', 'taxes', 'net_cash_flow', 'cumulative_cash_flow'] as $column)
                                            <th scope="col">{{ __('app.phase_nine.cash_flow.'.$column) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cashFlow->annualRows as $row)
                                        <tr>
                                            <th scope="row">{{ $row['year'] === 0 ? __('app.phase_nine.cash_flow.year_zero') : $row['year'] }}</th>
                                            @foreach (['collections', 'other_revenue', 'project_cost', 'financing_inflow', 'financing_cost', 'taxes', 'net_cash_flow', 'cumulative_cash_flow'] as $column)
                                                <td class="result-number" dir="ltr">{{ number_format((float) $row[$column], 2) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-xl-8">
            <section class="card app-card h-100">
                <div class="card-body">
                    <h2 class="h5 text-aqar">{{ __('app.phase_nine.sensitivity.title') }}</h2>
                    <p class="text-secondary small">{{ __('app.phase_nine.sensitivity.help') }}</p>
                    <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.projects.sensitivity', $project) }}">
                        @csrf
                        <div class="col-lg-5">
                            <label class="form-label" for="sensitivity-field">{{ __('app.phase_nine.sensitivity.field') }}</label>
                            <select class="form-select" id="sensitivity-field" name="field" required>
                                @foreach (config('aqar_metrics.analysis.sensitivity_fields') as $field)
                                    <option value="{{ $field }}" @selected(old('field', $sensitivityField) === $field)>{{ __('app.projects.attributes.'.$field) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label">{{ __('app.phase_nine.sensitivity.changes') }}</label>
                            <div class="d-flex gap-2" dir="ltr">
                                @foreach (old('changes', [-20, -10, 0, 10, 20]) as $index => $change)
                                    <input class="form-control form-control-sm text-center" name="changes[]" type="number" value="{{ $change }}" min="-100" max="100" aria-label="{{ __('app.phase_nine.sensitivity.change') }} {{ $index + 1 }}">
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <button class="btn btn-aqar w-100" type="submit">{{ __('app.phase_nine.sensitivity.run') }}</button>
                        </div>
                    </form>

                    @if ($sensitivityRows !== [])
                        <div class="table-responsive border rounded-3 mt-4">
                            <table class="table table-sm align-middle text-end mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @foreach (['change', 'input_value', 'revenue', 'cost', 'profit', 'margin'] as $column)
                                            <th scope="col">{{ __('app.phase_nine.sensitivity.'.$column) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sensitivityRows as $row)
                                        <tr>
                                            <th scope="row" dir="ltr">{{ $row['change_percent'] }}%</th>
                                            <td dir="ltr">{{ number_format((float) $row['input_value'], 2) }}</td>
                                            <td dir="ltr">{{ number_format((float) $row['total_revenue'], 2) }}</td>
                                            <td dir="ltr">{{ number_format((float) $row['total_cost'], 2) }}</td>
                                            <td dir="ltr">{{ number_format((float) $row['estimated_profit'], 2) }}</td>
                                            <td dir="ltr">{{ $row['profit_margin_percent'] === null ? '—' : number_format((float) $row['profit_margin_percent'], 2).'%' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card app-card mb-4">
                <div class="card-body">
                    <h2 class="h5 text-aqar">{{ __('app.phase_nine.scenarios.title') }}</h2>
                    @if ($canManageProject)
                        <form class="d-flex gap-2 mb-3" method="POST" action="{{ route('admin.projects.scenarios.store', $project) }}">
                            @csrf
                            <input class="form-control" name="scenario_name" type="text" maxlength="100" placeholder="{{ __('app.phase_nine.scenarios.name') }}" required>
                            <button class="btn btn-aqar text-nowrap" type="submit">{{ __('app.phase_nine.scenarios.create') }}</button>
                        </form>
                    @endif
                    <div class="list-group list-group-flush">
                        @forelse ($project->scenarios as $scenario)
                            <a class="list-group-item list-group-item-action px-0" href="{{ route('admin.projects.show', $scenario) }}">
                                {{ $scenario->scenario_name ?? $scenario->name }}
                            </a>
                        @empty
                            <p class="text-secondary small mb-0">{{ __('app.phase_nine.scenarios.empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </section>

            @if ($canManageProject)
                <section class="card app-card">
                    <div class="card-body">
                        <h2 class="h5 text-aqar">{{ __('app.phase_nine.sharing.title') }}</h2>
                        <p class="text-secondary small">{{ __('app.phase_nine.sharing.help') }}</p>
                        <form class="d-flex gap-2 mb-3" method="POST" action="{{ route('admin.projects.viewers.store', $project) }}">
                            @csrf
                            <input class="form-control" name="email" type="email" placeholder="{{ __('app.phase_nine.sharing.email') }}" required>
                            <button class="btn btn-aqar text-nowrap" type="submit">{{ __('app.phase_nine.sharing.share') }}</button>
                        </form>
                        @forelse ($project->viewers as $viewer)
                            <div class="d-flex justify-content-between align-items-center gap-2 border-top py-2">
                                <span>{{ $viewer->name }}<br><small class="text-secondary">{{ $viewer->email }}</small></span>
                                <form method="POST" action="{{ route('admin.projects.viewers.destroy', [$project, $viewer]) }}" data-confirm="{{ __('app.phase_nine.sharing.remove_confirmation', ['name' => $viewer->name]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('app.phase_nine.sharing.remove') }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-secondary small mb-0">{{ __('app.phase_nine.sharing.empty') }}</p>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
