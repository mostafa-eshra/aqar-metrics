@extends('layouts.admin')

@section('title', __('app.phase_nine.comparison.title').' | '.__('app.name'))
@section('context', __('app.phase_nine.comparison.title'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.projects.title')" :title="__('app.phase_nine.comparison.title')" :description="__('app.phase_nine.comparison.description')">
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.projects.index') }}">{{ __('app.projects.results.back_to_projects') }}</a>
        </x-slot:actions>
    </x-admin.page-header>

    <section class="card app-card mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET" id="comparison-form">
                <div class="col-lg-10 comparison-picker">
                    <label class="form-label" for="comparison-search">{{ __('app.phase_nine.comparison.select') }}</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><x-admin.icon name="search" size="18" /></span>
                        <input class="form-control" id="comparison-search" type="search" autocomplete="off" placeholder="{{ __('app.phase_nine.comparison.search_placeholder') }}" aria-controls="comparison-search-results" aria-expanded="false">
                    </div>
                    <select class="visually-hidden" id="comparison-projects" name="projects[]" multiple tabindex="-1" aria-hidden="true">
                        @foreach ($availableProjects as $availableProject)
                            <option value="{{ $availableProject->id }}" @selected(in_array($availableProject->id, $selectedIds, true))>
                                {{ $availableProject->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="comparison-search-results" id="comparison-search-results" role="listbox">
                        @foreach ($availableProjects as $availableProject)
                            <button
                                class="comparison-option"
                                type="button"
                                data-project-option="{{ $availableProject->id }}"
                                data-project-name="{{ $availableProject->name }}"
                                data-project-currency="{{ $availableProject->currency_code }}"
                                data-project-status="{{ $availableProject->status->label() }}"
                                role="option"
                            >
                                <span><strong class="d-block">{{ $availableProject->name }}</strong><small class="text-secondary">{{ $availableProject->owner->name }}</small></span>
                                <span class="text-secondary small">{{ $availableProject->currency_code }} · {{ $availableProject->status->label() }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="comparison-chips" id="comparison-chips" aria-live="polite"></div>
                    <div class="form-text">{{ __('app.phase_nine.comparison.minimum_help') }}</div>
                    @error('projects')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-aqar w-100" type="submit">{{ __('app.phase_nine.comparison.run') }}</button>
                </div>
            </form>
        </div>
    </section>

    @if ($hasDifferentCurrencies)
        <div class="alert alert-warning d-flex gap-2 align-items-start"><x-admin.icon name="alert" /><span>{{ __('app.phase_nine.comparison.different_currencies') }}</span></div>
    @endif

    @if ($comparison->isNotEmpty())
        @php
            $comparisonRows = [
                ['total_revenue', fn ($item) => $item['metrics']->revenues['total_with_hotel'], true, 'max'],
                ['total_cost', fn ($item) => $item['metrics']->totals['total_cost'], true, 'min'],
                ['estimated_profit', fn ($item) => $item['metrics']->totals['estimated_profit'], true, 'max'],
                ['profit_margin', fn ($item) => $item['metrics']->indicators['profit_margin_percent'], false, 'max'],
                ['return_on_cost', fn ($item) => $item['metrics']->indicators['return_on_cost_percent'], false, 'max'],
                ['npv', fn ($item) => $item['cash_flow']->netPresentValue, true, 'max'],
                ['irr', fn ($item) => $item['cash_flow']->internalRateOfReturnPercent, false, 'max'],
                ['payback', fn ($item) => $item['cash_flow']->paybackPeriodYears, false, 'min'],
            ];
            $projectColors = ['#176b5b', '#d3a84f', '#4263eb', '#ae3ec9', '#e8590c'];
        @endphp
        <section class="card app-card overflow-hidden" aria-labelledby="comparison-results-heading">
            <div class="card-header"><h2 class="card-title" id="comparison-results-heading">{{ __('app.phase_nine.comparison.results') }}</h2></div>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter text-end mb-0 comparison-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('app.phase_nine.comparison.metric') }}</th>
                            @foreach ($comparison as $item)
                                <th scope="col">
                                    <span class="d-inline-block rounded-circle me-1" style="width:.55rem;height:.55rem;background:{{ $projectColors[$loop->index % count($projectColors)] }}"></span>
                                    {{ $item['project']->name }}<br><small>{{ $item['project']->currency_code }} · {{ $item['project']->status->label() }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as [$key, $resolver, $isMoney, $preferred])
                            @php
                                $values = $comparison->map($resolver)->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value);
                                $best = $values->isEmpty() || ($isMoney && $hasDifferentCurrencies)
                                    ? null
                                    : ($preferred === 'min' ? $values->min() : $values->max());
                            @endphp
                            <tr>
                                <th scope="row">{{ __('app.phase_nine.comparison.'.$key) }}</th>
                                @foreach ($comparison as $item)
                                    @php $value = $resolver($item); @endphp
                                    <td class="result-number {{ $best !== null && (float) $value === $best ? 'bg-success-lt fw-bold' : '' }}" dir="ltr">
                                        {{ $value === null ? '—' : number_format((float) $value, 2) }}
                                        @if ($value !== null && $isMoney) {{ $item['project']->currency_code }} @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="card app-card">
            <x-admin.empty-state :title="__('app.phase_nine.comparison.empty_title')" :description="__('app.phase_nine.comparison.empty_description')" icon="compare" />
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const search = document.getElementById('comparison-search');
            const results = document.getElementById('comparison-search-results');
            const select = document.getElementById('comparison-projects');
            const chips = document.getElementById('comparison-chips');
            const options = [...document.querySelectorAll('[data-project-option]')];

            const render = () => {
                chips.replaceChildren();

                for (const option of options) {
                    const selectOption = select.querySelector(`option[value="${option.dataset.projectOption}"]`);
                    const isSelected = selectOption?.selected ?? false;
                    option.hidden = isSelected;
                    option.setAttribute('aria-selected', String(isSelected));

                    if (!isSelected) continue;

                    const chip = document.createElement('span');
                    chip.className = 'comparison-chip';
                    const label = document.createElement('span');
                    const name = document.createElement('strong');
                    name.textContent = option.dataset.projectName;
                    label.append(name, ` · ${option.dataset.projectCurrency} · ${option.dataset.projectStatus}`);
                    chip.appendChild(label);
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.setAttribute('aria-label', @json(__('app.phase_nine.comparison.remove')) + ' ' + option.dataset.projectName);
                    remove.textContent = '×';
                    remove.addEventListener('click', () => {
                        selectOption.selected = false;
                        render();
                    });
                    chip.appendChild(remove);
                    chips.appendChild(chip);
                }
            };

            const filter = () => {
                const term = search.value.trim().toLocaleLowerCase('ar');
                results.classList.add('show');
                search.setAttribute('aria-expanded', 'true');

                for (const option of options) {
                    const haystack = `${option.dataset.projectName} ${option.dataset.projectCurrency} ${option.dataset.projectStatus}`.toLocaleLowerCase('ar');
                    option.classList.toggle('d-none', !haystack.includes(term));
                }
            };

            search.addEventListener('focus', filter);
            search.addEventListener('input', filter);
            options.forEach(option => option.addEventListener('click', () => {
                select.querySelector(`option[value="${option.dataset.projectOption}"]`).selected = true;
                search.value = '';
                render();
                filter();
                search.focus();
            }));

            document.addEventListener('click', event => {
                if (!event.target.closest('.comparison-picker')) {
                    results.classList.remove('show');
                    search.setAttribute('aria-expanded', 'false');
                }
            });

            render();
        });
    </script>
@endpush
