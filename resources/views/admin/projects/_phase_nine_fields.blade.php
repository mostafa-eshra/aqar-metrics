@php
    $phaseDistribution = old(
        'residential_phase_distribution',
        $project->residential_phase_distribution ?? config('aqar_metrics.project.residential_phase_distribution'),
    );
    $cashFlowSchedule = old(
        'annual_cash_flow_schedule',
        $project->annual_cash_flow_schedule ?? \App\Services\ProjectCashFlowService::defaults((int) $project->project_duration_years),
    );
    $cashFlowDuration = max(1, (int) old('project_duration_years', $project->project_duration_years));
@endphp

<section class="project-form-section" aria-labelledby="phase-distribution-heading">
    <h2 class="h5 text-aqar" id="phase-distribution-heading">{{ __('app.phase_nine.phase_distribution.title') }}</h2>
    <p class="text-secondary small">{{ __('app.phase_nine.phase_distribution.help') }}</p>
    <div class="row g-3">
        @foreach (['phase_1', 'phase_2', 'phase_3'] as $phase)
            <div class="col-lg-4">
                <label class="form-label" for="residential_phase_distribution_{{ $phase }}">
                    {{ __('app.phase_nine.phases.'.$phase) }}
                </label>
                <div class="input-group" dir="ltr">
                    <input
                        class="form-control text-end @error('residential_phase_distribution.'.$phase) is-invalid @enderror"
                        id="residential_phase_distribution_{{ $phase }}"
                        name="residential_phase_distribution[{{ $phase }}]"
                        type="text"
                        inputmode="decimal"
                        value="{{ $phaseDistribution[$phase] ?? '' }}"
                    >
                    <span class="input-group-text">%</span>
                </div>
                @error('residential_phase_distribution.'.$phase)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        @endforeach
    </div>
    @error('residential_phase_distribution')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</section>

<section class="project-form-section" id="cash-flow-editor" aria-labelledby="cash-flow-heading">
    <h2 class="h5 text-aqar" id="cash-flow-heading">{{ __('app.phase_nine.cash_flow.title') }}</h2>
    <p class="text-secondary small">{{ __('app.phase_nine.cash_flow.help') }}</p>
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <label class="form-label" for="discount_rate_percent">{{ __('app.phase_nine.cash_flow.discount_rate') }}</label>
            <div class="input-group" dir="ltr">
                <input
                    class="form-control text-end @error('discount_rate_percent') is-invalid @enderror"
                    id="discount_rate_percent"
                    name="discount_rate_percent"
                    type="text"
                    inputmode="decimal"
                    value="{{ old('discount_rate_percent', $project->discount_rate_percent) }}"
                >
                <span class="input-group-text">%</span>
            </div>
            @error('discount_rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="table-responsive border rounded-3">
        <table class="table table-sm align-middle mb-0 cash-flow-input-table">
            <thead class="table-light">
                <tr>
                    <th scope="col">{{ __('app.phase_nine.cash_flow.year') }}</th>
                    @foreach (\App\Services\ProjectCashFlowService::INPUT_FIELDS as $field)
                        <th scope="col">{{ __('app.phase_nine.cash_flow.'.$field) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="cash-flow-input-body">
                @for ($year = 0; $year <= $cashFlowDuration; $year++)
                    <tr data-cash-flow-year="{{ $year }}">
                        <th class="text-nowrap" scope="row">
                            {{ $year === 0 ? __('app.phase_nine.cash_flow.year_zero') : __('app.phase_nine.cash_flow.year', ['year' => $year]) }}
                        </th>
                        @foreach (\App\Services\ProjectCashFlowService::INPUT_FIELDS as $field)
                            @php $cashFlowField = "annual_cash_flow_schedule.{$year}.{$field}"; @endphp
                            <td>
                                <input
                                    class="form-control form-control-sm text-end @error($cashFlowField) is-invalid @enderror"
                                    name="annual_cash_flow_schedule[{{ $year }}][{{ $field }}]"
                                    type="text"
                                    inputmode="decimal"
                                    value="{{ $cashFlowSchedule[$year][$field] ?? '0' }}"
                                    data-cash-flow-input
                                    data-year="{{ $year }}"
                                    data-field="{{ $field }}"
                                >
                                @error($cashFlowField)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    @error('annual_cash_flow_schedule')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</section>
