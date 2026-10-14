<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Project;

use App\Enums\CalculationVersion;
use App\Models\Project;
use App\Services\DTO\ProjectMetricsDTO;
use App\Support\DecimalMath;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
final class ProjectDashboardResource extends JsonResource
{
    /** @var array<string, mixed> */
    private array $metrics;

    public function __construct(Project $project, private readonly ProjectMetricsDTO $projectMetrics)
    {
        parent::__construct($project);

        $this->metrics = $projectMetrics->toArray();
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $project = $this->resource;
        $inputRows = $this->inputRows($project);
        $calculationRows = $this->calculationRows();
        $costChart = $this->costChart();

        return [
            'status_class' => match ($project->status->value) {
                'draft' => 'text-bg-warning',
                'completed' => 'text-bg-success',
                'archived' => 'text-bg-secondary',
            },
            'updated_at' => $project->updated_at
                ?->timezone(config('app.timezone'))
                ->locale(config('app.locale'))
                ->translatedFormat('j F Y، H:i'),
            'currency_label' => config("aqar_metrics.project.currencies.{$project->currency_code}")
                ?? $project->currency_code
                ?? trans('app.projects.not_available'),
            'summary_cards' => $this->summaryCards(),
            'input_rows' => $inputRows,
            'schedule_years' => $this->scheduleYears($project),
            'schedule_rows' => $this->scheduleRows($project),
            'revenue_rows' => $this->revenueRows(),
            'annual_collections' => $this->annualCollections(),
            'annual_collection_total' => $this->displayMetric(
                $this->projectMetrics->annualCollectionTotal,
                'money',
                'annual_collection_total',
            ),
            'construction_rows' => $this->constructionRows(),
            'expense_rows' => $this->expenseRows(),
            'calculation_rows' => [...$inputRows, ...$calculationRows],
            'annual_collection_chart' => $this->annualCollectionChart(),
            'cost_chart' => $costChart,
            'phase_comparison' => $this->phaseComparison(),
            'warnings' => $this->warnings($project),
            'calculation_notes' => $this->calculationNotes(),
            'calculation_version_label' => trans(
                "app.projects.results.calculation_versions.{$this->projectMetrics->calculationVersion->value}",
            ),
            'cost_chart_reconciles' => $costChart['reconciles'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function summaryCards(): array
    {
        return [
            $this->metricRow('total_revenue', 'revenues.total_with_hotel', 'money'),
            $this->metricRow('total_cost', 'totals.total_cost', 'money'),
            $this->metricRow('estimated_profit', 'totals.estimated_profit', 'money'),
            $this->metricRow('profit_margin', 'indicators.profit_margin_percent', 'percent'),
            $this->metricRow('return_on_cost', 'indicators.return_on_cost_percent', 'percent'),
            $this->metricRow('developer_saleable_area', 'areas.developer_saleable_area', 'sqm'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function inputRows(Project $project): array
    {
        $rows = [
            $this->projectInputRow($project, 'project_duration_years', 'years'),
            $this->projectInputRow($project, 'has_hotel', 'boolean'),
            $this->projectInputRow($project, 'building_coverage_percent', 'percent', 'assumption'),
        ];

        foreach (config('aqar_metrics.input_fields', []) as $field => $definition) {
            $rows[] = $this->projectInputRow(
                project: $project,
                field: $field,
                unit: (string) $definition['unit'],
            );
        }

        return $rows;
    }

    /** @return list<int> */
    private function scheduleYears(Project $project): array
    {
        $latestYear = $project->project_duration_years;

        foreach ([$project->residential_collection_schedule, $project->commercial_collection_schedule] as $schedule) {
            if (! is_array($schedule)) {
                continue;
            }

            foreach ($schedule as $group) {
                if (! is_array($group) || $group === []) {
                    continue;
                }

                $latestYear = max($latestYear, ...array_map('intval', array_keys($group)));
            }
        }

        return range(1, max(1, $latestYear));
    }

    /** @return list<array<string, mixed>> */
    private function scheduleRows(Project $project): array
    {
        $rows = [];
        $schedules = [
            'residential_collection_schedule' => $project->residential_collection_schedule,
            'commercial_collection_schedule' => $project->commercial_collection_schedule,
        ];

        foreach ($schedules as $field => $schedule) {
            if (! is_array($schedule)) {
                continue;
            }

            foreach ($schedule as $group => $years) {
                $values = [];

                foreach ($this->scheduleYears($project) as $year) {
                    $value = is_array($years) ? ($years[$year] ?? null) : null;
                    $values["year_{$year}"] = [
                        'raw_value' => $value,
                        'formatted_value' => $this->formatValue($value, 'percent'),
                        'is_outside_duration' => $year > $project->project_duration_years,
                    ];
                }

                $rows[] = [
                    'field' => $field,
                    'group' => $group,
                    'label' => trans("app.projects.collection_schedule.groups.{$group}"),
                    'values' => $values,
                ];
            }
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function revenueRows(): array
    {
        return [
            $this->metricRow('residential_phase_1_revenue', 'revenues.residential_phase_1', 'money'),
            $this->metricRow('residential_phase_2_revenue', 'revenues.residential_phase_2', 'money'),
            $this->metricRow('residential_phase_3_revenue', 'revenues.residential_phase_3', 'money'),
            $this->metricRow('commercial_group_1_revenue', 'revenues.commercial_group_1', 'money'),
            $this->metricRow('commercial_group_2_revenue', 'revenues.commercial_group_2', 'money'),
            $this->metricRow('residential_revenue_total', 'revenues.residential_total', 'money'),
            $this->metricRow('commercial_revenue_total', 'revenues.commercial_total', 'money'),
            $this->metricRow(
                'residential_and_commercial_revenue_total',
                'revenues.residential_and_commercial_total',
                'money',
            ),
            $this->metricRow('hotel_revenue', 'revenues.hotel', 'money'),
            $this->metricRow('total_revenue', 'revenues.total_with_hotel', 'money'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function annualCollections(): array
    {
        $rows = [];

        foreach ($this->projectMetrics->annualCollections as $year) {
            $groups = [];

            foreach ($this->collectionGroups() as $group) {
                $groups[$group] = [
                    'raw_value' => $year[$group],
                    'formatted_value' => $this->formatValue($year[$group], 'money'),
                    'reasons' => $this->reasonMessages("annual_collections.{$year['year']}.{$group}"),
                ];
            }

            $rows[] = [
                'year' => $year['year'],
                'groups' => $groups,
                'total' => $this->displayMetric($year['total'], 'money', "annual_collections.{$year['year']}.total"),
                'cumulative_total' => $this->displayMetric(
                    $year['cumulative_total'],
                    'money',
                    "annual_collections.{$year['year']}.cumulative_total",
                ),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function constructionRows(): array
    {
        return [
            $this->constructionRow('hotel_construction', 'hotel_construction_area', 'hotel_construction_cost_per_sqm', 'construction_costs.hotel'),
            $this->constructionRow('residential_construction', 'areas.maximum_buildable_area', 'residential_construction_cost_per_sqm', 'construction_costs.residential', true),
            $this->constructionRow('commercial_construction', 'commercial_construction_area', 'commercial_construction_cost_per_sqm', 'construction_costs.commercial'),
            $this->constructionRow('garage_construction', 'garage_construction_area', 'garage_construction_cost_per_sqm', 'construction_costs.garage'),
            $this->constructionRow('infrastructure_landscape_construction', 'infrastructure_landscape_area', 'infrastructure_landscape_cost_per_sqm', 'construction_costs.infrastructure_landscape'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function expenseRows(): array
    {
        return [
            $this->expenseRow('marketing_expense', 'marketing_percent', 'expenses.marketing'),
            $this->expenseRow('sales_expense', 'sales_percent', 'expenses.sales'),
            $this->expenseRow('administration_expense', 'administration_percent', 'expenses.administration'),
            $this->expenseRow('design_supervision_expense', 'design_supervision_percent', 'expenses.design_supervision'),
            [
                'key' => 'land_expense',
                'label' => trans('app.projects.results.metrics.land_expense'),
                'percent' => null,
                'amount' => $this->displayMetric(
                    data_get($this->metrics, 'expenses.land'),
                    'money',
                    'expenses.land',
                ),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function calculationRows(): array
    {
        $definitions = [
            ['maximum_buildable_area', 'areas.maximum_buildable_area', 'sqm'],
            ['developer_saleable_area', 'areas.developer_saleable_area', 'sqm'],
            ['residential_phase_1_area', 'areas.residential_phase_1_area', 'sqm'],
            ['residential_phase_2_area', 'areas.residential_phase_2_area', 'sqm'],
            ['residential_phase_3_area', 'areas.residential_phase_3_area', 'sqm'],
            ['residential_phase_1_revenue', 'revenues.residential_phase_1', 'money'],
            ['residential_phase_2_revenue', 'revenues.residential_phase_2', 'money'],
            ['residential_phase_3_revenue', 'revenues.residential_phase_3', 'money'],
            ['commercial_group_1_revenue', 'revenues.commercial_group_1', 'money'],
            ['commercial_group_2_revenue', 'revenues.commercial_group_2', 'money'],
            ['residential_revenue_total', 'revenues.residential_total', 'money'],
            ['commercial_revenue_total', 'revenues.commercial_total', 'money'],
            ['residential_and_commercial_revenue_total', 'revenues.residential_and_commercial_total', 'money'],
            ['hotel_revenue_effective', 'revenues.hotel', 'money'],
            ['total_revenue', 'revenues.total_with_hotel', 'money'],
            ['hotel_construction_cost', 'construction_costs.hotel', 'money'],
            ['residential_construction_cost', 'construction_costs.residential', 'money'],
            ['commercial_construction_cost', 'construction_costs.commercial', 'money'],
            ['garage_construction_cost', 'construction_costs.garage', 'money'],
            ['infrastructure_landscape_cost', 'construction_costs.infrastructure_landscape', 'money'],
            ['construction_cost_total', 'construction_costs.total', 'money'],
            ['marketing_expense', 'expenses.marketing', 'money'],
            ['sales_expense', 'expenses.sales', 'money'],
            ['administration_expense', 'expenses.administration', 'money'],
            ['design_supervision_expense', 'expenses.design_supervision', 'money'],
            ['land_expense', 'expenses.land', 'money'],
            ['expense_total_including_land', 'expenses.total_including_land', 'money'],
            ['total_cost', 'totals.total_cost', 'money'],
            ['estimated_profit', 'totals.estimated_profit', 'money'],
            ['profit_margin', 'indicators.profit_margin_percent', 'percent'],
            ['return_on_cost', 'indicators.return_on_cost_percent', 'percent'],
            ['residential_phase_1_contribution', 'revenue_contribution_percent.residential_phase_1', 'percent'],
            ['residential_phase_2_contribution', 'revenue_contribution_percent.residential_phase_2', 'percent'],
            ['residential_phase_3_contribution', 'revenue_contribution_percent.residential_phase_3', 'percent'],
            ['commercial_group_1_contribution', 'revenue_contribution_percent.commercial_group_1', 'percent'],
            ['commercial_group_2_contribution', 'revenue_contribution_percent.commercial_group_2', 'percent'],
            ['annual_collection_total', 'annual_collection_total', 'money'],
        ];
        $rows = [[
            'key' => 'calculation_version',
            'label' => trans('app.projects.results.metrics.calculation_version'),
            'raw_value' => $this->projectMetrics->calculationVersion->value,
            'formatted_value' => trans(
                "app.projects.results.calculation_versions.{$this->projectMetrics->calculationVersion->value}",
            ),
            'unit' => 'none',
            'unit_label' => trans('app.projects.results.units.none'),
            'type' => 'assumption',
            'type_label' => trans('app.projects.results.types.assumption'),
            'formula' => trans('app.projects.results.formulas.calculation_version'),
            'is_available' => true,
            'reasons' => [],
        ]];

        foreach ($definitions as [$key, $path, $unit]) {
            $rows[] = $this->metricRow($key, $path, $unit, true);
        }

        if ($this->projectMetrics->landCostOccurrences === 2) {
            $rows[] = [
                ...$this->metricRow('land_excel_adjustment', 'expenses.land', 'money', true),
                'formula' => trans('app.projects.results.formulas.land_excel_adjustment'),
            ];
        }

        return $rows;
    }

    /** @return array{available: bool, rows: list<array<string, mixed>>} */
    private function annualCollectionChart(): array
    {
        $maxTotal = null;

        foreach ($this->projectMetrics->annualCollections as $year) {
            if ($year['total'] === null) {
                return ['available' => false, 'rows' => []];
            }

            if ($maxTotal === null || DecimalMath::compare($year['total'], $maxTotal) > 0) {
                $maxTotal = $year['total'];
            }
        }

        if ($maxTotal === null) {
            return ['available' => false, 'rows' => []];
        }

        $rows = [];

        foreach ($this->projectMetrics->annualCollections as $year) {
            $segments = [];

            foreach ($this->collectionGroups() as $group) {
                $value = $year[$group];
                $segments[] = [
                    'key' => $group,
                    'label' => trans("app.projects.collection_schedule.groups.{$group}"),
                    'raw_value' => $value,
                    'formatted_value' => $this->formatValue($value, 'money'),
                    'share_percent' => $this->ratioForDisplay($value, $year['total']),
                ];
            }

            $rows[] = [
                'year' => $year['year'],
                'raw_total' => $year['total'],
                'formatted_total' => $this->formatValue($year['total'], 'money'),
                'height_percent' => $this->ratioForDisplay($year['total'], $maxTotal),
                'segments' => $segments,
            ];
        }

        return ['available' => true, 'rows' => $rows];
    }

    /** @return array{available: bool, total: array<string, mixed>, items: list<array<string, mixed>>, reconciles: bool} */
    private function costChart(): array
    {
        $totalCost = data_get($this->metrics, 'totals.total_cost');
        $definitions = [
            ['hotel_construction_cost', 'construction_costs.hotel'],
            ['residential_construction_cost', 'construction_costs.residential'],
            ['commercial_construction_cost', 'construction_costs.commercial'],
            ['garage_construction_cost', 'construction_costs.garage'],
            ['infrastructure_landscape_cost', 'construction_costs.infrastructure_landscape'],
            ['marketing_expense', 'expenses.marketing'],
            ['sales_expense', 'expenses.sales'],
            ['administration_expense', 'expenses.administration'],
            ['design_supervision_expense', 'expenses.design_supervision'],
            ['land_expense', 'expenses.land'],
        ];

        if ($this->projectMetrics->landCostOccurrences === 2) {
            $definitions[] = ['land_excel_adjustment', 'expenses.land'];
        }

        $values = [];
        $items = [];

        foreach ($definitions as [$key, $path]) {
            $value = data_get($this->metrics, $path);

            if ($value === null) {
                return [
                    'available' => false,
                    'total' => $this->displayMetric($totalCost, 'money', 'totals.total_cost'),
                    'items' => [],
                    'reconciles' => false,
                ];
            }

            $values[] = $value;
            $items[] = [
                'key' => $key,
                'label' => trans("app.projects.results.metrics.{$key}"),
                'raw_value' => $value,
                'formatted_value' => $this->formatValue($value, 'money'),
                'share_percent' => $this->ratioForDisplay($value, $totalCost),
            ];
        }

        $componentTotal = DecimalMath::sum($values, (int) config('aqar_metrics.calculation.money_scale'));
        $reconciles = $totalCost !== null && DecimalMath::compare($componentTotal, $totalCost) === 0;

        return [
            'available' => $totalCost !== null,
            'total' => $this->displayMetric($totalCost, 'money', 'totals.total_cost'),
            'items' => $items,
            'reconciles' => $reconciles,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function phaseComparison(): array
    {
        $rows = [];

        foreach ([1, 2, 3] as $phase) {
            $rows[] = [
                'key' => "residential_phase_{$phase}",
                'label' => trans("app.projects.collection_schedule.groups.residential_phase_{$phase}"),
                'area' => $this->displayMetric(
                    data_get($this->metrics, "areas.residential_phase_{$phase}_area"),
                    'sqm',
                    "areas.residential_phase_{$phase}_area",
                ),
                'price' => $this->displayInput("residential_phase_{$phase}_price_per_sqm", 'money_per_sqm'),
                'revenue' => $this->displayMetric(
                    data_get($this->metrics, "revenues.residential_phase_{$phase}"),
                    'money',
                    "revenues.residential_phase_{$phase}",
                ),
                'contribution' => $this->displayMetric(
                    data_get($this->metrics, "revenue_contribution_percent.residential_phase_{$phase}"),
                    'percent',
                    "revenue_contribution_percent.residential_phase_{$phase}",
                ),
            ];
        }

        return $rows;
    }

    /** @return list<array{key: string, message: string}> */
    private function warnings(Project $project): array
    {
        $warnings = [];
        $missingFields = [];

        foreach ($this->projectMetrics->unavailableReasons as $reasons) {
            foreach ($reasons as $reason) {
                if (str_starts_with($reason, 'missing_input.')) {
                    $missingFields[] = substr($reason, strlen('missing_input.'));
                }
            }
        }

        $missingFields = array_values(array_unique($missingFields));

        if ($missingFields !== []) {
            $labels = array_map(
                static fn (string $field): string => trans("app.projects.attributes.{$field}"),
                $missingFields,
            );
            $warnings[] = [
                'key' => 'missing_inputs',
                'message' => trans('app.projects.results.warnings.missing_inputs', [
                    'fields' => implode('، ', $labels),
                ]),
            ];
        }

        if ($project->has_hotel
            && $project->hotel_revenue !== null
            && ! DecimalMath::isZero((string) $project->hotel_revenue)) {
            $warnings[] = [
                'key' => 'hotel_revenue_not_distributed',
                'message' => trans('app.projects.results.warnings.hotel_revenue_not_distributed'),
            ];
        }

        if ($this->commercialAreasDiffer($project)) {
            $warnings[] = [
                'key' => 'commercial_area_difference',
                'message' => trans('app.projects.results.warnings.commercial_area_difference'),
            ];
        }

        if ($this->projectMetrics->annualCollectionTotal === null) {
            $warnings[] = [
                'key' => 'annual_collections_unavailable',
                'message' => trans('app.projects.results.warnings.annual_collections_unavailable'),
            ];
        }

        if ($this->projectMetrics->calculationVersion === CalculationVersion::EXCEL_SOURCE_V1) {
            $warnings[] = [
                'key' => 'excel_source_land_double_count',
                'message' => trans('app.projects.results.warnings.excel_source_land_double_count'),
            ];
        }

        return $warnings;
    }

    /** @return list<string> */
    private function calculationNotes(): array
    {
        return array_map(
            static fn (string $note): string => trans("app.projects.results.notes.{$note}"),
            $this->projectMetrics->calculationNotes,
        );
    }

    /** @return array<string, mixed> */
    private function projectInputRow(
        Project $project,
        string $field,
        string $unit,
        string $type = 'input',
    ): array {
        $value = $project->getAttribute($field);

        return [
            'key' => $field,
            'label' => trans("app.projects.attributes.{$field}"),
            'raw_value' => $value,
            'formatted_value' => $this->formatValue($value, $unit),
            'unit' => $unit,
            'unit_label' => $this->unitLabel($unit),
            'type' => $type,
            'type_label' => trans("app.projects.results.types.{$type}"),
            'formula' => null,
            'is_available' => $value !== null,
            'reasons' => $value === null
                ? [trans('app.projects.results.unavailable_reasons.missing_input', ['field' => trans("app.projects.attributes.{$field}")])]
                : [],
        ];
    }

    /** @return array<string, mixed> */
    private function metricRow(
        string $key,
        string $path,
        string $unit,
        bool $withFormula = false,
    ): array {
        $value = data_get($this->metrics, $path);

        return [
            'key' => $key,
            'label' => trans("app.projects.results.metrics.{$key}"),
            'raw_value' => $value,
            'formatted_value' => $this->formatValue($value, $unit),
            'unit' => $unit,
            'unit_label' => $this->unitLabel($unit),
            'type' => 'computed',
            'type_label' => trans('app.projects.results.types.computed'),
            'formula' => $withFormula ? trans("app.projects.results.formulas.{$key}") : null,
            'is_available' => $value !== null,
            'reasons' => $this->reasonMessages($path),
        ];
    }

    /** @return array<string, mixed> */
    private function constructionRow(
        string $key,
        string $areaPath,
        string $rateField,
        string $totalPath,
        bool $areaIsMetric = false,
    ): array {
        $area = $areaIsMetric
            ? $this->displayMetric(data_get($this->metrics, $areaPath), 'sqm', $areaPath)
            : $this->displayInput($areaPath, 'sqm');

        return [
            'key' => $key,
            'metric_key' => match ($totalPath) {
                'construction_costs.infrastructure_landscape' => 'infrastructure_landscape_cost',
                default => str_replace('construction_costs.', '', $totalPath).'_construction_cost',
            },
            'label' => trans("app.projects.results.metrics.{$key}"),
            'area' => $area,
            'rate' => $this->displayInput($rateField, 'money_per_sqm'),
            'total' => $this->displayMetric(data_get($this->metrics, $totalPath), 'money', $totalPath),
        ];
    }

    /** @return array<string, mixed> */
    private function expenseRow(string $key, string $percentField, string $amountPath): array
    {
        return [
            'key' => $key,
            'label' => trans("app.projects.results.metrics.{$key}"),
            'percent' => $this->displayInput($percentField, 'percent'),
            'amount' => $this->displayMetric(data_get($this->metrics, $amountPath), 'money', $amountPath),
        ];
    }

    /** @return array<string, mixed> */
    private function displayInput(string $field, string $unit): array
    {
        $value = $this->resource->getAttribute($field);

        return [
            'raw_value' => $value,
            'formatted_value' => $this->formatValue($value, $unit),
            'unit_label' => $this->unitLabel($unit),
            'is_available' => $value !== null,
            'reasons' => $value === null
                ? [trans('app.projects.results.unavailable_reasons.missing_input', ['field' => trans("app.projects.attributes.{$field}")])]
                : [],
        ];
    }

    /** @return array<string, mixed> */
    private function displayMetric(mixed $value, string $unit, string $path): array
    {
        return [
            'raw_value' => $value,
            'formatted_value' => $this->formatValue($value, $unit),
            'unit_label' => $this->unitLabel($unit),
            'is_available' => $value !== null,
            'reasons' => $this->reasonMessages($path),
        ];
    }

    /** @return list<string> */
    private function reasonMessages(string $path): array
    {
        $reasons = $this->projectMetrics->unavailableReasons[$path] ?? [];

        return array_map(function (string $reason): string {
            if (str_starts_with($reason, 'missing_input.')) {
                $field = substr($reason, strlen('missing_input.'));

                return trans('app.projects.results.unavailable_reasons.missing_input', [
                    'field' => trans("app.projects.attributes.{$field}"),
                ]);
            }

            if (str_contains($reason, 'schedule')) {
                return trans('app.projects.results.unavailable_reasons.schedule');
            }

            if (str_starts_with($reason, 'zero_denominator.') || $reason === 'zero_divisor') {
                return trans('app.projects.results.unavailable_reasons.zero_denominator');
            }

            if ($reason === 'invalid_project_duration') {
                return trans('app.projects.results.unavailable_reasons.invalid_project_duration');
            }

            return trans('app.projects.results.unavailable_reasons.incomplete_data');
        }, $reasons);
    }

    private function formatValue(mixed $value, string $unit): string
    {
        if ($value === null) {
            return trans('app.projects.not_available');
        }

        if ($unit === 'boolean') {
            return $value
                ? trans('app.projects.hotel_options.yes')
                : trans('app.projects.hotel_options.no');
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $scale = match ($unit) {
            'years' => 0,
            'sqm' => (int) config('aqar_metrics.calculation.area_scale'),
            'percent' => (int) config('aqar_metrics.calculation.percent_scale'),
            default => (int) config('aqar_metrics.calculation.money_scale'),
        };
        $scaled = (string) BigDecimal::of((string) $value)->toScale($scale, RoundingMode::HalfUp);
        [$whole, $fraction] = array_pad(explode('.', $scaled, 2), 2, '');
        $isNegative = str_starts_with($whole, '-');
        $unsignedWhole = $isNegative ? substr($whole, 1) : $whole;
        $formattedWhole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $unsignedWhole) ?? $unsignedWhole;

        return ($isNegative ? '-' : '').$formattedWhole.($scale > 0 ? ".{$fraction}" : '');
    }

    private function unitLabel(string $unit): string
    {
        return match ($unit) {
            'money' => (string) ($this->resource->currency_code ?? trans('app.projects.not_available')),
            'money_per_sqm' => trans('app.projects.results.units.money_per_sqm', [
                'currency' => $this->resource->currency_code ?? trans('app.projects.not_available'),
            ]),
            default => trans("app.projects.results.units.{$unit}"),
        };
    }

    private function ratioForDisplay(?string $numerator, ?string $denominator): string
    {
        if ($numerator === null || $denominator === null || DecimalMath::isZero($denominator)) {
            return '0.00';
        }

        return DecimalMath::ratioPercentage($numerator, $denominator, 2);
    }

    private function commercialAreasDiffer(Project $project): bool
    {
        if ($project->commercial_construction_area === null
            || $project->commercial_sales_1_area === null
            || $project->commercial_sales_2_area === null) {
            return false;
        }

        $salesArea = DecimalMath::sum([
            (string) $project->commercial_sales_1_area,
            (string) $project->commercial_sales_2_area,
        ], (int) config('aqar_metrics.calculation.area_scale'));

        return DecimalMath::compare((string) $project->commercial_construction_area, $salesArea) !== 0;
    }

    /** @return list<string> */
    private function collectionGroups(): array
    {
        return [
            'residential_phase_1',
            'residential_phase_2',
            'residential_phase_3',
            'commercial_group_1',
            'commercial_group_2',
        ];
    }
}
