@php
    $workflowSteps = [
        'basic' => __('app.projects.sections.basic'),
        'schedule' => __('app.projects.sections.collection_schedule'),
        'land' => __('app.projects.sections.land'),
        'residential' => __('app.projects.sections.residential'),
        'commercial' => __('app.projects.sections.commercial'),
        'construction' => __('app.projects.sections.construction'),
        'expenses' => __('app.projects.sections.expenses'),
        'hotel' => __('app.projects.sections.hotel'),
        'advanced' => __('app.phase_nine.phase_distribution.title'),
        'review' => __('app.projects.workflow.review'),
    ];

    $errorStep = static function (string $field): string {
        return match (true) {
            str_starts_with($field, 'residential_collection_schedule'),
            str_starts_with($field, 'commercial_collection_schedule') => 'schedule',
            in_array($field, ['land_area', 'land_cost', 'building_coverage_percent', 'developer_share_percent'], true) => 'land',
            str_starts_with($field, 'residential_phase_') && ! str_starts_with($field, 'residential_phase_distribution') => 'residential',
            str_starts_with($field, 'commercial_sales_') => 'commercial',
            str_contains($field, 'construction'), str_starts_with($field, 'garage_'), str_starts_with($field, 'infrastructure_') => 'construction',
            in_array($field, ['marketing_percent', 'sales_percent', 'administration_percent', 'design_supervision_percent'], true) => 'expenses',
            str_starts_with($field, 'hotel_') => 'hotel',
            str_starts_with($field, 'residential_phase_distribution'), str_starts_with($field, 'annual_cash_flow_schedule'), $field === 'discount_rate_percent' => 'advanced',
            default => 'basic',
        };
    };

    $errorTarget = static function (string $field): string {
        if (str_contains($field, 'collection_schedule')) {
            return 'collection-schedule-editor';
        }

        if (str_starts_with($field, 'residential_phase_distribution')) {
            return 'phase-distribution-heading';
        }

        if (str_starts_with($field, 'annual_cash_flow_schedule')) {
            return 'cash-flow-heading';
        }

        return str_replace('.', '_', $field);
    };
@endphp

<div class="project-form-shell">
    <aside class="form-step-sidebar" aria-label="{{ __('app.projects.workflow.title') }}">
        <div class="form-step-card">
            <p class="small fw-bold px-2 pt-2 mb-2">{{ __('app.projects.workflow.title') }}</p>
            <nav class="form-step-nav">
                @foreach ($workflowSteps as $step => $label)
                    <button class="form-step-link {{ $loop->first ? 'active' : '' }}" type="button" data-form-step-target="{{ $step }}" @if($loop->first) aria-current="step" @endif>
                        <span class="form-step-number">{{ $loop->iteration }}</span>
                        <span>{{ $label }}</span>
                        <x-admin.icon name="check" size="15" class="form-step-check opacity-0" />
                    </button>
                @endforeach
            </nav>
        </div>
    </aside>

    <div class="project-form-content">
        <div class="form-progress-mobile" aria-live="polite">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <strong id="mobile-step-title">{{ reset($workflowSteps) }}</strong>
                <span class="small text-secondary" id="mobile-step-count">{{ __('app.projects.workflow.step', ['current' => 1, 'total' => count($workflowSteps)]) }}</span>
            </div>
            <div class="progress"><div class="progress-bar bg-primary" id="mobile-step-progress" style="width: 10%"></div></div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger validation-summary" role="alert" tabindex="-1" id="project-validation-summary">
                <strong>{{ __('app.ui.validation_title') }}</strong>
                <p class="small mb-2">{{ __('app.projects.workflow.validation_help') }}</p>
                <ul class="mb-0">
                    @foreach ($errors->getMessages() as $field => $messages)
                        @foreach ($messages as $message)
                            <li><a href="#{{ $errorTarget($field) }}" data-error-step="{{ $errorStep($field) }}">{{ $message }}</a></li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endif

<div class="alert alert-info d-flex gap-2 align-items-start" role="note">
    <span aria-hidden="true">ⓘ</span>
    <div>
        <div>{{ __('app.projects.manual_entry_note') }}</div>
        <small>{{ __('app.projects.duration_help') }}</small>
    </div>
</div>

<section class="project-form-section" id="basic-step" aria-labelledby="basic-heading" data-form-step="basic">
    <h2 class="h5 text-aqar" id="basic-heading">{{ __('app.projects.sections.basic') }}</h2>
    <div class="row g-3">
        <div class="col-lg-6">
            <label class="form-label" for="name">{{ __('app.projects.attributes.name') }}</label>
            <input
                class="form-control @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $project->name) }}"
                maxlength="255"
                required
                autofocus
            >
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-3">
            <label class="form-label" for="location">{{ __('app.projects.attributes.location') }}</label>
            <input
                class="form-control @error('location') is-invalid @enderror"
                id="location"
                name="location"
                type="text"
                value="{{ old('location', $project->location) }}"
                maxlength="255"
            >
            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-3">
            <label class="form-label" for="currency_code">{{ __('app.projects.attributes.currency_code') }}</label>
            <select class="form-select @error('currency_code') is-invalid @enderror" id="currency_code" name="currency_code">
                <option value="">—</option>
                @foreach (config('aqar_metrics.project.currencies') as $code => $label)
                    <option value="{{ $code }}" @selected(old('currency_code', $project->currency_code) === $code)>
                        {{ $label }} ({{ $code }})
                    </option>
                @endforeach
            </select>
            @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-3">
            <label class="form-label" for="project_duration_years">{{ __('app.projects.attributes.project_duration_years') }}</label>
            <div class="input-group" dir="rtl">
                <input
                    class="form-control text-end @error('project_duration_years') is-invalid @enderror"
                    dir="ltr"
                    id="project_duration_years"
                    name="project_duration_years"
                    type="text"
                    inputmode="numeric"
                    value="{{ old('project_duration_years', $project->project_duration_years) }}"
                    required
                >
                <span class="input-group-text">{{ __('app.projects.units.years') }}</span>
            </div>
            @error('project_duration_years')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-3">
            <label class="form-label" for="status">{{ __('app.projects.attributes.status') }}</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach ([\App\Enums\ProjectStatus::DRAFT, \App\Enums\ProjectStatus::COMPLETED] as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project->status?->value) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">{{ __('app.projects.status_help') }}</div>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-3">
            <label class="form-label" for="has_hotel">{{ __('app.projects.attributes.has_hotel') }}</label>
            <select class="form-select @error('has_hotel') is-invalid @enderror" id="has_hotel" name="has_hotel" required>
                <option value="0" @selected((string) old('has_hotel', (int) $project->has_hotel) === '0')>{{ __('app.projects.hotel_options.no') }}</option>
                <option value="1" @selected((string) old('has_hotel', (int) $project->has_hotel) === '1')>{{ __('app.projects.hotel_options.yes') }}</option>
            </select>
            @error('has_hotel')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="description">{{ __('app.projects.attributes.description') }}</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $project->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</section>

@include('admin.projects._collection_schedule', ['project' => $project])

<section class="project-form-section" id="land-step" aria-labelledby="land-heading" data-form-step="land">
    <h2 class="h5 text-aqar" id="land-heading">{{ __('app.projects.sections.land') }}</h2>
    <div class="row g-3">
        @include('admin.projects._number_field', ['name' => 'land_area', 'unit' => 'sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'land_cost', 'unit' => 'money', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'building_coverage_percent', 'unit' => 'percent', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'developer_share_percent', 'unit' => 'percent', 'project' => $project])
    </div>
</section>

<section class="project-form-section" id="residential-step" aria-labelledby="residential-heading" data-form-step="residential">
    <h2 class="h5 text-aqar" id="residential-heading">{{ __('app.projects.sections.residential') }}</h2>
    <div class="row g-3">
        @include('admin.projects._number_field', ['name' => 'residential_phase_1_price_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project, 'columnClass' => 'col-lg-4'])
        @include('admin.projects._number_field', ['name' => 'residential_phase_2_price_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project, 'columnClass' => 'col-lg-4'])
        @include('admin.projects._number_field', ['name' => 'residential_phase_3_price_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project, 'columnClass' => 'col-lg-4'])
    </div>
</section>

<section class="project-form-section" id="commercial-step" aria-labelledby="commercial-heading" data-form-step="commercial">
    <h2 class="h5 text-aqar" id="commercial-heading">{{ __('app.projects.sections.commercial') }}</h2>
    <div class="row g-3">
        @include('admin.projects._number_field', ['name' => 'commercial_sales_1_area', 'unit' => 'sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'commercial_sales_1_price_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'commercial_sales_2_area', 'unit' => 'sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'commercial_sales_2_price_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project])
    </div>
</section>

<section class="project-form-section" id="construction-step" aria-labelledby="construction-heading" data-form-step="construction">
    <h2 class="h5 text-aqar" id="construction-heading">{{ __('app.projects.sections.construction') }}</h2>
    <div class="row g-3">
        @include('admin.projects._number_field', ['name' => 'residential_construction_cost_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'commercial_construction_area', 'unit' => 'sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'commercial_construction_cost_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'garage_construction_area', 'unit' => 'sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'garage_construction_cost_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'infrastructure_landscape_area', 'unit' => 'sqm', 'project' => $project])
        @include('admin.projects._number_field', ['name' => 'infrastructure_landscape_cost_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project])
    </div>
</section>

<section class="project-form-section" id="expenses-step" aria-labelledby="expenses-heading" data-form-step="expenses">
    <h2 class="h5 text-aqar" id="expenses-heading">{{ __('app.projects.sections.expenses') }}</h2>
    <div class="row g-3">
        @include('admin.projects._number_field', ['name' => 'marketing_percent', 'unit' => 'percent', 'project' => $project, 'columnClass' => 'col-sm-6 col-xl-3'])
        @include('admin.projects._number_field', ['name' => 'sales_percent', 'unit' => 'percent', 'project' => $project, 'columnClass' => 'col-sm-6 col-xl-3'])
        @include('admin.projects._number_field', ['name' => 'administration_percent', 'unit' => 'percent', 'project' => $project, 'columnClass' => 'col-sm-6 col-xl-3'])
        @include('admin.projects._number_field', ['name' => 'design_supervision_percent', 'unit' => 'percent', 'project' => $project, 'columnClass' => 'col-sm-6 col-xl-3'])
    </div>
</section>

<section class="project-form-section hotel-step" id="hotel-step" aria-labelledby="hotel-heading" data-form-step="hotel">
    <h2 class="h5 text-aqar" id="hotel-heading">{{ __('app.projects.sections.hotel') }}</h2>
    <div class="row g-3">
        @include('admin.projects._number_field', ['name' => 'hotel_construction_area', 'unit' => 'sqm', 'project' => $project, 'columnClass' => 'col-lg-4'])
        @include('admin.projects._number_field', ['name' => 'hotel_construction_cost_per_sqm', 'unit' => 'money_per_sqm', 'project' => $project, 'columnClass' => 'col-lg-4'])
        @include('admin.projects._number_field', ['name' => 'hotel_revenue', 'unit' => 'money', 'project' => $project, 'columnClass' => 'col-lg-4'])
    </div>
</section>

@include('admin.projects._phase_nine_fields', ['project' => $project])

<section class="project-form-section" id="review-step" aria-labelledby="review-heading" data-form-step="review">
    <h2 class="h5 text-aqar" id="review-heading">{{ __('app.projects.workflow.review') }}</h2>
    <p class="form-step-description">{{ __('app.projects.workflow.review_description') }}</p>

<div class="preview-panel d-none" id="preview-panel" aria-live="polite">
    <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
        <div>
            <h2 class="h5 mb-1">{{ __('app.projects.preview.title') }}</h2>
            <p class="text-secondary small mb-0">{{ __('app.projects.preview.description') }}</p>
        </div>
        <span class="badge text-bg-warning">{{ __('app.projects.preview.title') }}</span>
    </div>
    <div class="alert alert-danger d-none" id="preview-error" role="alert"></div>
    <div class="row g-3" id="preview-results">
        @foreach (['revenue', 'cost', 'profit', 'margin'] as $metric)
            <div class="col-sm-6 col-xl-3">
                <div class="preview-metric">
                    <span>{{ __('app.projects.preview.'.$metric) }}</span>
                    <strong id="preview-{{ $metric }}">—</strong>
                </div>
            </div>
        @endforeach
    </div>
    <div class="alert alert-warning d-none mt-3 mb-0" id="preview-schedule-warning" role="alert">
        {{ __('app.projects.preview.annual_collection_unavailable') }}
    </div>
    <div class="mt-4 d-none" id="preview-annual-collection">
        <h3 class="h6 mb-1">{{ __('app.projects.preview.annual_collection_title') }}</h3>
        <p class="text-secondary small">{{ __('app.projects.preview.annual_collection_description') }}</p>
        <div class="table-responsive border rounded-3">
            <table class="table table-sm align-middle mb-0 annual-collection-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('app.projects.preview.year') }}</th>
                        <th class="text-end" scope="col">{{ __('app.projects.preview.year_collection') }}</th>
                        <th class="text-end" scope="col">{{ __('app.projects.preview.cumulative_collection') }}</th>
                    </tr>
                </thead>
                <tbody id="preview-annual-collection-body"></tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <th scope="row">{{ __('app.projects.preview.collection_total') }}</th>
                        <td class="text-end" colspan="2" id="preview-annual-collection-total">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</section>
    </div>
</div>

<div class="sticky-form-actions">
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" id="form-step-previous" type="button">
            <x-admin.icon name="chevron-right" size="17" />{{ __('app.projects.workflow.previous') }}
        </button>
        <button class="btn btn-outline-secondary" id="form-step-next" type="button">
            {{ __('app.projects.workflow.next') }}<x-admin.icon name="chevron-left" size="17" />
        </button>
    </div>
    <div class="d-flex flex-wrap gap-2 form-action-primary">
        <button class="btn btn-outline-primary" id="preview-button" type="button">{{ __('app.projects.preview.button') }}</button>
        <button class="btn btn-aqar" type="submit">{{ __('app.save') }}</button>
        <a class="btn btn-ghost-secondary" href="{{ route('admin.projects.index') }}">{{ __('app.cancel') }}</a>
    </div>
</div>
