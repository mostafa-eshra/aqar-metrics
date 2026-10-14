@extends('layouts.admin')

@section('title', __('app.phase_nine.comparison.title').' | '.__('app.name'))

@section('content')
    <div class="mb-4">
        <a class="small text-decoration-none" href="{{ route('admin.projects.index') }}">{{ __('app.projects.results.back_to_projects') }}</a>
        <h1 class="h2 fw-bold text-aqar mt-3 mb-1">{{ __('app.phase_nine.comparison.title') }}</h1>
        <p class="text-secondary mb-0">{{ __('app.phase_nine.comparison.description') }}</p>
    </div>

    <section class="card app-card mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-lg-10">
                    <label class="form-label" for="comparison-projects">{{ __('app.phase_nine.comparison.select') }}</label>
                    <select class="form-select" id="comparison-projects" name="projects[]" multiple size="6" required>
                        @foreach ($availableProjects as $availableProject)
                            <option value="{{ $availableProject->id }}" @selected(in_array($availableProject->id, $selectedIds, true))>
                                {{ $availableProject->name }} — {{ $availableProject->currency_code }} — {{ $availableProject->owner->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-aqar w-100" type="submit">{{ __('app.phase_nine.comparison.run') }}</button>
                </div>
            </form>
        </div>
    </section>

    @if ($hasDifferentCurrencies)
        <div class="alert alert-warning">{{ __('app.phase_nine.comparison.different_currencies') }}</div>
    @endif

    @if ($comparison->isNotEmpty())
        @php
            $comparisonRows = [
                ['total_revenue', fn ($item) => $item['metrics']->revenues['total_with_hotel'], true],
                ['total_cost', fn ($item) => $item['metrics']->totals['total_cost'], true],
                ['estimated_profit', fn ($item) => $item['metrics']->totals['estimated_profit'], true],
                ['profit_margin', fn ($item) => $item['metrics']->indicators['profit_margin_percent'], false],
                ['return_on_cost', fn ($item) => $item['metrics']->indicators['return_on_cost_percent'], false],
                ['npv', fn ($item) => $item['cash_flow']->netPresentValue, true],
                ['irr', fn ($item) => $item['cash_flow']->internalRateOfReturnPercent, false],
                ['payback', fn ($item) => $item['cash_flow']->paybackPeriodYears, false],
            ];
        @endphp
        <section class="card app-card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-end mb-0 comparison-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('app.phase_nine.comparison.metric') }}</th>
                            @foreach ($comparison as $item)
                                <th scope="col">{{ $item['project']->name }}<br><small>{{ $item['project']->currency_code }}</small></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as [$key, $resolver, $isMoney])
                            <tr>
                                <th scope="row">{{ __('app.phase_nine.comparison.'.$key) }}</th>
                                @foreach ($comparison as $item)
                                    @php $value = $resolver($item); @endphp
                                    <td class="result-number" dir="ltr">
                                        {{ $value === null ? '—' : number_format((float) $value, 2) }}
                                        @if ($value !== null && $isMoney)
                                            {{ $item['project']->currency_code }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
