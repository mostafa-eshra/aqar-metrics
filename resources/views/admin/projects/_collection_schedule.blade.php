@php
    $durationYears = max(1, (int) old('project_duration_years', $project->project_duration_years));
    $collectionScheduleSections = [
        [
            'field' => 'residential_collection_schedule',
            'title' => __('app.projects.collection_schedule.residential_title'),
            'schedule' => old('residential_collection_schedule', $project->residential_collection_schedule ?? []),
            'groups' => [
                'residential_phase_1' => __('app.projects.collection_schedule.groups.residential_phase_1'),
                'residential_phase_2' => __('app.projects.collection_schedule.groups.residential_phase_2'),
                'residential_phase_3' => __('app.projects.collection_schedule.groups.residential_phase_3'),
            ],
        ],
        [
            'field' => 'commercial_collection_schedule',
            'title' => __('app.projects.collection_schedule.commercial_title'),
            'schedule' => old('commercial_collection_schedule', $project->commercial_collection_schedule ?? []),
            'groups' => [
                'commercial_group_1' => __('app.projects.collection_schedule.groups.commercial_group_1'),
                'commercial_group_2' => __('app.projects.collection_schedule.groups.commercial_group_2'),
            ],
        ],
    ];

    $hasExcludedCollectionYears = false;
    $isInitialScheduleComplete = true;

    foreach ($collectionScheduleSections as $section) {
        foreach ($section['groups'] as $group => $label) {
            $activeTotal = 0;

            foreach (($section['schedule'][$group] ?? []) as $year => $percentage) {
                if ((int) $year >= 1 && (int) $year <= $durationYears) {
                    $activeTotal += (float) $percentage;
                }

                if ((int) $year > $durationYears && (float) $percentage > 0) {
                    $hasExcludedCollectionYears = true;
                }
            }

            if (abs($activeTotal - 100) >= 0.00001) {
                $isInitialScheduleComplete = false;
            }
        }
    }

    $isInitialScheduleComplete = $isInitialScheduleComplete && ! $hasExcludedCollectionYears;
@endphp

<section
    class="project-form-section"
    id="collection-schedule-editor"
    data-form-step="schedule"
    aria-labelledby="collection-schedule-heading"
    data-duration-input="project_duration_years"
    data-required-total="100"
>
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
        <div>
            <h2 class="h5 text-aqar mb-1" id="collection-schedule-heading">
                {{ __('app.projects.sections.collection_schedule') }}
            </h2>
            <p class="text-secondary small mb-0">{{ __('app.projects.collection_schedule.help') }}</p>
            <p class="text-secondary small mb-0">{{ __('app.projects.collection_schedule.template_note') }}</p>
        </div>
        <span class="badge text-bg-light border" id="collection-duration-badge">
            {{ trans_choice('app.projects.duration_years', $durationYears, ['count' => $durationYears]) }}
        </span>
    </div>

    <div class="alert alert-warning {{ $hasExcludedCollectionYears ? '' : 'd-none' }}" id="excluded-years-alert" role="alert">
        <strong>{{ __('app.projects.collection_schedule.excluded_title') }}</strong>
        <div class="small mt-1">{{ __('app.projects.collection_schedule.excluded_help') }}</div>
        <div class="mt-3" id="excluded-years-groups">
            @foreach ($collectionScheduleSections as $section)
                @foreach ($section['groups'] as $group => $groupLabel)
                    @php
                        $excludedYears = collect($section['schedule'][$group] ?? [])
                            ->filter(fn ($percentage, $year) => (int) $year > $durationYears && (float) $percentage > 0)
                            ->sortKeys(SORT_NUMERIC);
                    @endphp

                    @if ($excludedYears->isNotEmpty())
                        <div
                            class="excluded-years-group"
                            data-excluded-group
                            data-schedule-field="{{ $section['field'] }}"
                            data-schedule-group="{{ $group }}"
                            data-group-label="{{ $groupLabel }}"
                        >
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <strong>{{ $groupLabel }}</strong>
                                <button class="btn btn-sm btn-outline-success" type="button" data-accept-redistribution disabled>
                                    {{ __('app.projects.collection_schedule.accept_redistribution') }}
                                </button>
                            </div>
                            <div class="d-flex flex-wrap gap-2" data-excluded-values>
                                @foreach ($excludedYears as $year => $percentage)
                                    <span class="excluded-year-value" data-excluded-year="{{ $year }}">
                                        {{ __('app.projects.collection_schedule.year', ['year' => $year]) }}:
                                        <strong>{{ $percentage }}%</strong>
                                        <input
                                            type="hidden"
                                            name="{{ $section['field'] }}[{{ $group }}][{{ $year }}]"
                                            value="{{ $percentage }}"
                                            data-excluded-input
                                            data-year="{{ $year }}"
                                        >
                                    </span>
                                @endforeach
                            </div>
                            <div class="small mt-2" data-redistribution-hint>
                                {{ __('app.projects.collection_schedule.redistribution_pending') }}
                            </div>
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>
    </div>

    @foreach ($collectionScheduleSections as $section)
        <div class="collection-schedule-block" data-schedule-table data-schedule-field="{{ $section['field'] }}">
            <h3 class="h6 mb-2">{{ $section['title'] }}</h3>
            <div class="table-responsive border rounded-3">
                <table class="table table-sm align-middle text-center mb-0 collection-schedule-table">
                    <thead class="table-light">
                        <tr data-schedule-header>
                            <th class="text-start" scope="col">{{ __('app.projects.collection_schedule.group') }}</th>
                            @for ($year = 1; $year <= $durationYears; $year++)
                                <th scope="col">{{ __('app.projects.collection_schedule.year', ['year' => $year]) }}</th>
                            @endfor
                            <th scope="col">{{ __('app.projects.collection_schedule.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section['groups'] as $group => $groupLabel)
                            @php
                                $yearPercentages = $section['schedule'][$group] ?? [];
                                $activeTotal = 0;
                            @endphp
                            <tr
                                data-schedule-row
                                data-schedule-field="{{ $section['field'] }}"
                                data-schedule-group="{{ $group }}"
                                data-group-label="{{ $groupLabel }}"
                            >
                                <th class="text-start text-nowrap" scope="row">{{ $groupLabel }}</th>
                                @for ($year = 1; $year <= $durationYears; $year++)
                                    @php
                                        $percentage = $yearPercentages[$year] ?? '0';
                                        $activeTotal += (float) $percentage;
                                    @endphp
                                    <td data-year-cell="{{ $year }}">
                                        <div class="input-group input-group-sm schedule-percentage-input" dir="rtl">
                                            <input
                                                class="form-control text-end"
                                                dir="ltr"
                                                type="text"
                                                inputmode="decimal"
                                                name="{{ $section['field'] }}[{{ $group }}][{{ $year }}]"
                                                value="{{ $percentage }}"
                                                aria-label="{{ __('app.projects.collection_schedule.input_label', ['group' => $groupLabel, 'year' => $year]) }}"
                                                data-schedule-input
                                                data-year="{{ $year }}"
                                            >
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                @endfor
                                <td class="text-nowrap">
                                    <span class="badge {{ abs($activeTotal - 100) < 0.00001 ? 'text-bg-success' : 'text-bg-warning' }}" data-group-total>
                                        {{ number_format($activeTotal, 2, '.', '') }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @foreach ($section['groups'] as $group => $groupLabel)
                @if ($errors->has($section['field'].'.'.$group))
                    <div class="text-danger small mt-1">{{ $errors->first($section['field'].'.'.$group) }}</div>
                @endif
            @endforeach
        </div>
    @endforeach

    <div
        class="alert {{ $isInitialScheduleComplete ? 'alert-success' : 'alert-warning' }} mt-3 mb-0"
        id="collection-schedule-status"
        role="status"
    >
        {{ __($isInitialScheduleComplete ? 'app.projects.collection_schedule.complete' : 'app.projects.collection_schedule.incomplete') }}
    </div>
</section>
