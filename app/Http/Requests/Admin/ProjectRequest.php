<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCollectionScheduleService;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User || ! $user->is_active) {
            return false;
        }

        $project = $this->route('project');

        return ! $project instanceof Project
            || $user->canAccessProjectOwnedBy((int) $project->user_id);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        $isCompleted = $this->input('status') === ProjectStatus::COMPLETED->value;
        $requiredWhenCompleted = $isCompleted ? 'required' : 'nullable';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'currency_code' => [$requiredWhenCompleted, 'string', Rule::in(array_keys(config('aqar_metrics.project.currencies')))],
            'project_duration_years' => ['required', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', Rule::in([
                ProjectStatus::DRAFT->value,
                ProjectStatus::COMPLETED->value,
            ])],
            'has_hotel' => ['required', 'boolean'],
            'residential_collection_schedule' => [
                $requiredWhenCompleted,
                'array:'.implode(',', ProjectCollectionScheduleService::RESIDENTIAL_GROUPS),
            ],
            'commercial_collection_schedule' => [
                $requiredWhenCompleted,
                'array:'.implode(',', ProjectCollectionScheduleService::COMMERCIAL_GROUPS),
            ],
        ];

        foreach (Project::MONEY_FIELDS as $field) {
            $presence = $field === 'hotel_revenue' && (! $isCompleted || ! $this->boolean('has_hotel'))
                ? 'nullable'
                : $requiredWhenCompleted;
            $rules[$field] = [$presence, 'numeric', 'min:0', 'max:9999999999999999.99', 'decimal:0,2'];
        }

        foreach (Project::AREA_FIELDS as $field) {
            $rules[$field] = [$requiredWhenCompleted, 'numeric', 'min:0', 'max:99999999999999.9999', 'decimal:0,4'];
        }

        foreach (Project::PERCENT_FIELDS as $field) {
            $rules[$field] = [$requiredWhenCompleted, 'numeric', 'min:0', 'max:100', 'decimal:0,2'];
        }

        $this->addCollectionScheduleRules(
            rules: $rules,
            scheduleField: 'residential_collection_schedule',
            groups: ProjectCollectionScheduleService::RESIDENTIAL_GROUPS,
            required: $isCompleted,
        );
        $this->addCollectionScheduleRules(
            rules: $rules,
            scheduleField: 'commercial_collection_schedule',
            groups: ProjectCollectionScheduleService::COMMERCIAL_GROUPS,
            required: $isCompleted,
        );

        return $rules;
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateScheduleYearKeys(
                validator: $validator,
                scheduleField: 'residential_collection_schedule',
                groups: ProjectCollectionScheduleService::RESIDENTIAL_GROUPS,
            );
            $this->validateScheduleYearKeys(
                validator: $validator,
                scheduleField: 'commercial_collection_schedule',
                groups: ProjectCollectionScheduleService::COMMERCIAL_GROUPS,
            );

            if ($validator->errors()->isNotEmpty() || $this->input('status') !== ProjectStatus::COMPLETED->value) {
                return;
            }

            $this->validateCompletedInputs($validator);
            $this->validateCollectionSchedule(
                validator: $validator,
                scheduleField: 'residential_collection_schedule',
                groups: ProjectCollectionScheduleService::RESIDENTIAL_GROUPS,
            );
            $this->validateCollectionSchedule(
                validator: $validator,
                scheduleField: 'commercial_collection_schedule',
                groups: ProjectCollectionScheduleService::COMMERCIAL_GROUPS,
            );
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return trans('app.projects.attributes');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $normalized = [];

        foreach ([...Project::MONEY_FIELDS, ...Project::AREA_FIELDS, ...Project::PERCENT_FIELDS, 'project_duration_years'] as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = self::normalizeNumber($this->input($field));
            }
        }

        foreach (['residential_collection_schedule', 'commercial_collection_schedule'] as $scheduleField) {
            if ($this->exists($scheduleField)) {
                $normalized[$scheduleField] = self::normalizeSchedule($this->input($scheduleField));
            }
        }

        if ($this->exists('currency_code')) {
            $normalized['currency_code'] = mb_strtoupper(trim((string) $this->input('currency_code')));
        }

        $this->merge($normalized);
    }

    /**
     * @param  array<string, list<mixed>>  $rules
     * @param  list<string>  $groups
     */
    private function addCollectionScheduleRules(
        array &$rules,
        string $scheduleField,
        array $groups,
        bool $required,
    ): void {
        foreach ($groups as $group) {
            $rules["{$scheduleField}.{$group}"] = [$required ? 'required' : 'sometimes', 'array'];
            $rules["{$scheduleField}.{$group}.*"] = [
                'required',
                'numeric',
                'min:0',
                'max:100',
                'decimal:0,2',
            ];
        }
    }

    private function validateCompletedInputs(Validator $validator): void
    {
        if (! BigDecimal::of((string) $this->input('land_area'))->isGreaterThan('0')) {
            $validator->errors()->add('land_area', trans('app.projects.validation.land_area_positive'));
        }

        foreach ([1, 2] as $groupNumber) {
            $areaField = "commercial_sales_{$groupNumber}_area";
            $priceField = "commercial_sales_{$groupNumber}_price_per_sqm";

            if (BigDecimal::of((string) $this->input($areaField))->isGreaterThan('0')
                && ! BigDecimal::of((string) $this->input($priceField))->isGreaterThan('0')) {
                $validator->errors()->add($priceField, trans('app.projects.validation.commercial_price_positive'));
            }
        }

        if ($this->boolean('has_hotel')) {
            return;
        }

        foreach (['hotel_construction_area', 'hotel_revenue'] as $field) {
            $value = $this->input($field);

            if ($value !== null && ! BigDecimal::of((string) $value)->isZero()) {
                $validator->errors()->add($field, trans('app.projects.validation.hotel_value_zero'));
            }
        }
    }

    /** @param list<string> $groups */
    private function validateScheduleYearKeys(Validator $validator, string $scheduleField, array $groups): void
    {
        $schedule = $this->input($scheduleField);

        if (! is_array($schedule)) {
            return;
        }

        foreach ($groups as $group) {
            $yearPercentages = $schedule[$group] ?? null;

            if (! is_array($yearPercentages)) {
                continue;
            }

            foreach (array_keys($yearPercentages) as $year) {
                $canonicalYear = (string) (int) $year;

                if ((int) $year < 1 || $canonicalYear !== (string) $year) {
                    $validator->errors()->add(
                        "{$scheduleField}.{$group}",
                        trans('app.projects.validation.schedule_year_keys'),
                    );

                    break;
                }
            }
        }
    }

    /** @param list<string> $groups */
    private function validateCollectionSchedule(Validator $validator, string $scheduleField, array $groups): void
    {
        $durationYears = $this->integer('project_duration_years');
        $expectedYears = range(1, $durationYears);
        $schedule = $this->input($scheduleField);

        if (! is_array($schedule)) {
            return;
        }

        foreach ($groups as $group) {
            $yearPercentages = $schedule[$group] ?? null;

            if (! is_array($yearPercentages)) {
                continue;
            }

            $years = array_map('intval', array_keys($yearPercentages));
            sort($years);

            if ($years !== $expectedYears) {
                $validator->errors()->add(
                    "{$scheduleField}.{$group}",
                    trans('app.projects.validation.schedule_years'),
                );

                continue;
            }

            $total = BigDecimal::zero();

            foreach ($yearPercentages as $percentage) {
                $total = $total->plus((string) $percentage);
            }

            if ($total->compareTo('100') !== 0) {
                $validator->errors()->add(
                    "{$scheduleField}.{$group}",
                    trans('app.projects.validation.schedule_total'),
                );
            }
        }
    }

    private static function normalizeNumber(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٬', '،', ',', '٫', ' '],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '', '', '', '.', ''],
            trim($value),
        );
    }

    private static function normalizeSchedule(mixed $schedule): mixed
    {
        if (! is_array($schedule)) {
            return $schedule;
        }

        foreach ($schedule as $group => $yearPercentages) {
            if (! is_array($yearPercentages)) {
                continue;
            }

            foreach ($yearPercentages as $year => $percentage) {
                $schedule[$group][$year] = self::normalizeNumber($percentage);
            }
        }

        return $schedule;
    }
}
