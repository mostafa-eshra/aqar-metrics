<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CalculationVersion;
use App\Models\Project;
use App\Services\DTO\ProjectMetricsDTO;
use App\Support\DecimalMath;

final class ProjectCalculationService
{
    /** @var list<string> */
    private const RESIDENTIAL_GROUPS = [
        'residential_phase_1',
        'residential_phase_2',
        'residential_phase_3',
    ];

    /** @var list<string> */
    private const COMMERCIAL_GROUPS = [
        'commercial_group_1',
        'commercial_group_2',
    ];

    public static function calculate(Project $project): ProjectMetricsDTO
    {
        $moneyScale = (int) config('aqar_metrics.calculation.money_scale');
        $areaScale = (int) config('aqar_metrics.calculation.area_scale');
        $percentScale = (int) config('aqar_metrics.calculation.percent_scale');
        $durationYears = $project->project_duration_years;
        $calculationVersion = self::calculationVersion($project);
        $unavailableReasons = [];

        $maximumBuildableArea = self::percentage(
            self::input($project, 'land_area'),
            self::input($project, 'building_coverage_percent'),
            $areaScale,
        );
        $developerSaleableArea = self::percentage(
            $maximumBuildableArea,
            self::input($project, 'developer_share_percent'),
            $areaScale,
        );
        $residentialPhaseArea = self::divide(
            $developerSaleableArea,
            self::available((string) config('aqar_metrics.project.residential_phase_count')),
            $areaScale,
        );

        $areas = [
            'maximum_buildable_area' => self::record($unavailableReasons, 'areas.maximum_buildable_area', $maximumBuildableArea),
            'developer_saleable_area' => self::record($unavailableReasons, 'areas.developer_saleable_area', $developerSaleableArea),
            'residential_phase_1_area' => self::record($unavailableReasons, 'areas.residential_phase_1_area', $residentialPhaseArea),
            'residential_phase_2_area' => self::record($unavailableReasons, 'areas.residential_phase_2_area', $residentialPhaseArea),
            'residential_phase_3_area' => self::record($unavailableReasons, 'areas.residential_phase_3_area', $residentialPhaseArea),
        ];

        $revenueMetrics = [
            'residential_phase_1' => self::multiply($residentialPhaseArea, self::input($project, 'residential_phase_1_price_per_sqm'), $moneyScale),
            'residential_phase_2' => self::multiply($residentialPhaseArea, self::input($project, 'residential_phase_2_price_per_sqm'), $moneyScale),
            'residential_phase_3' => self::multiply($residentialPhaseArea, self::input($project, 'residential_phase_3_price_per_sqm'), $moneyScale),
            'commercial_group_1' => self::multiply(
                self::input($project, 'commercial_sales_1_area'),
                self::input($project, 'commercial_sales_1_price_per_sqm'),
                $moneyScale,
            ),
            'commercial_group_2' => self::multiply(
                self::input($project, 'commercial_sales_2_area'),
                self::input($project, 'commercial_sales_2_price_per_sqm'),
                $moneyScale,
            ),
        ];
        $residentialRevenueTotal = self::sum([
            $revenueMetrics['residential_phase_1'],
            $revenueMetrics['residential_phase_2'],
            $revenueMetrics['residential_phase_3'],
        ], $moneyScale);
        $commercialRevenueTotal = self::sum([
            $revenueMetrics['commercial_group_1'],
            $revenueMetrics['commercial_group_2'],
        ], $moneyScale);
        $residentialAndCommercialRevenueTotal = self::sum([
            $residentialRevenueTotal,
            $commercialRevenueTotal,
        ], $moneyScale);
        $hotelRevenue = $project->has_hotel
            ? self::input($project, 'hotel_revenue')
            : self::available(self::zero($moneyScale));
        $totalRevenueWithHotel = self::sum([
            $residentialAndCommercialRevenueTotal,
            $hotelRevenue,
        ], $moneyScale);

        $revenues = [
            'residential_phase_1' => self::record($unavailableReasons, 'revenues.residential_phase_1', $revenueMetrics['residential_phase_1']),
            'residential_phase_2' => self::record($unavailableReasons, 'revenues.residential_phase_2', $revenueMetrics['residential_phase_2']),
            'residential_phase_3' => self::record($unavailableReasons, 'revenues.residential_phase_3', $revenueMetrics['residential_phase_3']),
            'commercial_group_1' => self::record($unavailableReasons, 'revenues.commercial_group_1', $revenueMetrics['commercial_group_1']),
            'commercial_group_2' => self::record($unavailableReasons, 'revenues.commercial_group_2', $revenueMetrics['commercial_group_2']),
            'residential_total' => self::record($unavailableReasons, 'revenues.residential_total', $residentialRevenueTotal),
            'commercial_total' => self::record($unavailableReasons, 'revenues.commercial_total', $commercialRevenueTotal),
            'residential_and_commercial_total' => self::record(
                $unavailableReasons,
                'revenues.residential_and_commercial_total',
                $residentialAndCommercialRevenueTotal,
            ),
            'hotel' => self::record($unavailableReasons, 'revenues.hotel', $hotelRevenue),
            'total_with_hotel' => self::record($unavailableReasons, 'revenues.total_with_hotel', $totalRevenueWithHotel),
        ];

        $hotelConstructionCost = $project->has_hotel
            ? self::multiply(
                self::input($project, 'hotel_construction_area'),
                self::input($project, 'hotel_construction_cost_per_sqm'),
                $moneyScale,
            )
            : self::available(self::zero($moneyScale));
        $residentialConstructionCost = self::multiply(
            $maximumBuildableArea,
            self::input($project, 'residential_construction_cost_per_sqm'),
            $moneyScale,
        );
        $commercialConstructionCost = self::multiply(
            self::input($project, 'commercial_construction_area'),
            self::input($project, 'commercial_construction_cost_per_sqm'),
            $moneyScale,
        );
        $garageConstructionCost = self::multiply(
            self::input($project, 'garage_construction_area'),
            self::input($project, 'garage_construction_cost_per_sqm'),
            $moneyScale,
        );
        $infrastructureLandscapeCost = self::multiply(
            self::input($project, 'infrastructure_landscape_area'),
            self::input($project, 'infrastructure_landscape_cost_per_sqm'),
            $moneyScale,
        );
        $constructionCostTotal = self::sum([
            $hotelConstructionCost,
            $residentialConstructionCost,
            $commercialConstructionCost,
            $garageConstructionCost,
            $infrastructureLandscapeCost,
        ], $moneyScale);

        $constructionCosts = [
            'hotel' => self::record($unavailableReasons, 'construction_costs.hotel', $hotelConstructionCost),
            'residential' => self::record($unavailableReasons, 'construction_costs.residential', $residentialConstructionCost),
            'commercial' => self::record($unavailableReasons, 'construction_costs.commercial', $commercialConstructionCost),
            'garage' => self::record($unavailableReasons, 'construction_costs.garage', $garageConstructionCost),
            'infrastructure_landscape' => self::record(
                $unavailableReasons,
                'construction_costs.infrastructure_landscape',
                $infrastructureLandscapeCost,
            ),
            'total' => self::record($unavailableReasons, 'construction_costs.total', $constructionCostTotal),
        ];

        $marketingExpense = self::percentage(
            $residentialAndCommercialRevenueTotal,
            self::input($project, 'marketing_percent'),
            $moneyScale,
        );
        $salesExpense = self::percentage(
            $residentialAndCommercialRevenueTotal,
            self::input($project, 'sales_percent'),
            $moneyScale,
        );
        $administrationExpense = self::percentage(
            $residentialAndCommercialRevenueTotal,
            self::input($project, 'administration_percent'),
            $moneyScale,
        );
        $designSupervisionExpense = self::percentage(
            $constructionCostTotal,
            self::input($project, 'design_supervision_percent'),
            $moneyScale,
        );
        $landExpense = self::input($project, 'land_cost');
        $expenseTotalIncludingLand = self::sum([
            $marketingExpense,
            $salesExpense,
            $administrationExpense,
            $designSupervisionExpense,
            $landExpense,
        ], $moneyScale);

        $expenses = [
            'marketing' => self::record($unavailableReasons, 'expenses.marketing', $marketingExpense),
            'sales' => self::record($unavailableReasons, 'expenses.sales', $salesExpense),
            'administration' => self::record($unavailableReasons, 'expenses.administration', $administrationExpense),
            'design_supervision' => self::record($unavailableReasons, 'expenses.design_supervision', $designSupervisionExpense),
            'land' => self::record($unavailableReasons, 'expenses.land', $landExpense),
            'total_including_land' => self::record(
                $unavailableReasons,
                'expenses.total_including_land',
                $expenseTotalIncludingLand,
            ),
        ];

        $totalCostComponents = [$constructionCostTotal, $expenseTotalIncludingLand];
        $landCostOccurrences = 1;

        if ($calculationVersion === CalculationVersion::EXCEL_SOURCE_V1) {
            $totalCostComponents[] = $landExpense;
            $landCostOccurrences = 2;
        }

        $totalCost = self::sum($totalCostComponents, $moneyScale);
        $estimatedProfit = self::subtract($totalRevenueWithHotel, $totalCost, $moneyScale);
        $totals = [
            'total_cost' => self::record($unavailableReasons, 'totals.total_cost', $totalCost),
            'estimated_profit' => self::record($unavailableReasons, 'totals.estimated_profit', $estimatedProfit),
        ];

        $profitMargin = self::ratioPercentage(
            $estimatedProfit,
            $totalRevenueWithHotel,
            $percentScale,
            'zero_denominator.total_revenue_with_hotel',
        );
        $returnOnCost = self::ratioPercentage(
            $estimatedProfit,
            $totalCost,
            $percentScale,
            'zero_denominator.total_cost',
        );
        $indicators = [
            'profit_margin_percent' => self::record(
                $unavailableReasons,
                'indicators.profit_margin_percent',
                $profitMargin,
            ),
            'return_on_cost_percent' => self::record(
                $unavailableReasons,
                'indicators.return_on_cost_percent',
                $returnOnCost,
            ),
        ];

        $revenueContributionPercent = [];

        foreach ($revenueMetrics as $group => $revenueMetric) {
            $contribution = self::ratioPercentage(
                $revenueMetric,
                $residentialAndCommercialRevenueTotal,
                $percentScale,
                'zero_denominator.residential_and_commercial_revenue_total',
            );
            $revenueContributionPercent[$group] = self::record(
                $unavailableReasons,
                "revenue_contribution_percent.{$group}",
                $contribution,
            );
        }

        [$annualCollections, $annualCollectionTotal] = self::calculateAnnualCollections(
            project: $project,
            revenueMetrics: $revenueMetrics,
            durationYears: $durationYears,
            moneyScale: $moneyScale,
            percentScale: $percentScale,
            unavailableReasons: $unavailableReasons,
        );

        return new ProjectMetricsDTO(
            calculationVersion: $calculationVersion,
            projectDurationYears: $durationYears,
            landCostOccurrences: $landCostOccurrences,
            areas: $areas,
            revenues: $revenues,
            constructionCosts: $constructionCosts,
            expenses: $expenses,
            totals: $totals,
            indicators: $indicators,
            revenueContributionPercent: $revenueContributionPercent,
            annualCollections: $annualCollections,
            annualCollectionTotal: $annualCollectionTotal,
            unavailableReasons: $unavailableReasons,
            calculationNotes: [
                $calculationVersion === CalculationVersion::EXCEL_SOURCE_V1
                    ? 'land_cost_counted_twice_for_excel_compatibility'
                    : 'land_cost_counted_once',
                'hotel_revenue_not_distributed_annually',
                'marketing_sales_administration_base_excludes_hotel_revenue',
                'annual_collection_rounding_reconciled_to_group_revenue',
            ],
        );
    }

    private static function calculationVersion(Project $project): CalculationVersion
    {
        $version = $project->calculation_version;

        if ($version instanceof CalculationVersion) {
            return $version;
        }

        return CalculationVersion::from((string) $version);
    }

    /** @return array{value: string|null, reasons: list<string>} */
    private static function input(Project $project, string $field): array
    {
        $value = $project->getAttribute($field);

        if ($value === null) {
            return self::unavailable("missing_input.{$field}");
        }

        return self::available((string) $value);
    }

    /** @return array{value: string, reasons: list<string>} */
    private static function available(string $value): array
    {
        return ['value' => $value, 'reasons' => []];
    }

    /** @return array{value: null, reasons: list<string>} */
    private static function unavailable(string ...$reasons): array
    {
        return ['value' => null, 'reasons' => array_values(array_unique($reasons))];
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  $left
     * @param  array{value: string|null, reasons: list<string>}  $right
     * @return array{value: string|null, reasons: list<string>}
     */
    private static function multiply(array $left, array $right, int $scale): array
    {
        $reasons = self::reasons($left, $right);

        if ($reasons !== []) {
            return self::unavailable(...$reasons);
        }

        return self::available(DecimalMath::multiply($left['value'], $right['value'], $scale));
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  $amount
     * @param  array{value: string|null, reasons: list<string>}  $percent
     * @return array{value: string|null, reasons: list<string>}
     */
    private static function percentage(array $amount, array $percent, int $scale): array
    {
        $reasons = self::reasons($amount, $percent);

        if ($reasons !== []) {
            return self::unavailable(...$reasons);
        }

        return self::available(DecimalMath::percentage($amount['value'], $percent['value'], $scale));
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  $dividend
     * @param  array{value: string|null, reasons: list<string>}  $divisor
     * @return array{value: string|null, reasons: list<string>}
     */
    private static function divide(array $dividend, array $divisor, int $scale): array
    {
        $reasons = self::reasons($dividend, $divisor);

        if ($reasons !== []) {
            return self::unavailable(...$reasons);
        }

        if (DecimalMath::isZero($divisor['value'])) {
            return self::unavailable('zero_divisor');
        }

        return self::available(DecimalMath::divide($dividend['value'], $divisor['value'], $scale));
    }

    /**
     * @param  list<array{value: string|null, reasons: list<string>}>  $metrics
     * @return array{value: string|null, reasons: list<string>}
     */
    private static function sum(array $metrics, int $scale): array
    {
        $reasons = self::reasons(...$metrics);

        if ($reasons !== []) {
            return self::unavailable(...$reasons);
        }

        return self::available(DecimalMath::sum(
            array_map(static fn (array $metric): string => $metric['value'], $metrics),
            $scale,
        ));
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  $minuend
     * @param  array{value: string|null, reasons: list<string>}  $subtrahend
     * @return array{value: string|null, reasons: list<string>}
     */
    private static function subtract(array $minuend, array $subtrahend, int $scale): array
    {
        $reasons = self::reasons($minuend, $subtrahend);

        if ($reasons !== []) {
            return self::unavailable(...$reasons);
        }

        return self::available(DecimalMath::subtract($minuend['value'], $subtrahend['value'], $scale));
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  $numerator
     * @param  array{value: string|null, reasons: list<string>}  $denominator
     * @return array{value: string|null, reasons: list<string>}
     */
    private static function ratioPercentage(
        array $numerator,
        array $denominator,
        int $scale,
        string $zeroDenominatorReason,
    ): array {
        $reasons = self::reasons($numerator, $denominator);

        if ($reasons !== []) {
            return self::unavailable(...$reasons);
        }

        if (DecimalMath::isZero($denominator['value'])) {
            return self::unavailable($zeroDenominatorReason);
        }

        return self::available(DecimalMath::ratioPercentage(
            $numerator['value'],
            $denominator['value'],
            $scale,
        ));
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  ...$metrics
     * @return list<string>
     */
    private static function reasons(array ...$metrics): array
    {
        $reasons = [];

        foreach ($metrics as $metric) {
            $reasons = [...$reasons, ...$metric['reasons']];
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @param  array<string, list<string>>  $unavailableReasons
     * @param  array{value: string|null, reasons: list<string>}  $metric
     */
    private static function record(array &$unavailableReasons, string $key, array $metric): ?string
    {
        if ($metric['value'] === null) {
            $unavailableReasons[$key] = $metric['reasons'];
        }

        return $metric['value'];
    }

    private static function zero(int $scale): string
    {
        return DecimalMath::sum([], $scale);
    }

    /**
     * @param  array<string, array{value: string|null, reasons: list<string>}>  $revenueMetrics
     * @param  array<string, list<string>>  $unavailableReasons
     * @return array{0: list<array{
     *     year: int,
     *     residential_phase_1: ?string,
     *     residential_phase_2: ?string,
     *     residential_phase_3: ?string,
     *     commercial_group_1: ?string,
     *     commercial_group_2: ?string,
     *     total: ?string,
     *     cumulative_total: ?string
     * }>, 1: ?string}
     */
    private static function calculateAnnualCollections(
        Project $project,
        array $revenueMetrics,
        int $durationYears,
        int $moneyScale,
        int $percentScale,
        array &$unavailableReasons,
    ): array {
        if ($durationYears < 1) {
            $unavailableReasons['annual_collections'] = ['invalid_project_duration'];
            $unavailableReasons['annual_collection_total'] = ['invalid_project_duration'];

            return [[], null];
        }

        $scheduleGroups = [];
        $residentialSchedule = $project->residential_collection_schedule;
        $commercialSchedule = $project->commercial_collection_schedule;

        foreach (self::RESIDENTIAL_GROUPS as $group) {
            $scheduleGroups[$group] = self::normalizeScheduleGroup(
                $residentialSchedule,
                $group,
                $durationYears,
                $percentScale,
            );
        }

        foreach (self::COMMERCIAL_GROUPS as $group) {
            $scheduleGroups[$group] = self::normalizeScheduleGroup(
                $commercialSchedule,
                $group,
                $durationYears,
                $percentScale,
            );
        }

        $groupAnnualMetrics = [];

        foreach ($revenueMetrics as $group => $revenueMetric) {
            $groupAnnualMetrics[$group] = self::calculateAnnualGroup(
                revenueMetric: $revenueMetric,
                schedule: $scheduleGroups[$group]['schedule'],
                scheduleReasons: $scheduleGroups[$group]['reasons'],
                durationYears: $durationYears,
                moneyScale: $moneyScale,
            );
        }

        $annualCollections = [];
        $cumulativeTotal = null;

        for ($year = 1; $year <= $durationYears; $year++) {
            $annualMetrics = [];

            foreach (array_keys($revenueMetrics) as $group) {
                $annualMetrics[$group] = $groupAnnualMetrics[$group][$year];
            }

            $yearTotal = self::sum(array_values($annualMetrics), $moneyScale);
            $cumulativeTotal = $cumulativeTotal === null
                ? $yearTotal
                : self::sum([$cumulativeTotal, $yearTotal], $moneyScale);

            $annualCollections[] = [
                'year' => $year,
                'residential_phase_1' => self::record(
                    $unavailableReasons,
                    "annual_collections.{$year}.residential_phase_1",
                    $annualMetrics['residential_phase_1'],
                ),
                'residential_phase_2' => self::record(
                    $unavailableReasons,
                    "annual_collections.{$year}.residential_phase_2",
                    $annualMetrics['residential_phase_2'],
                ),
                'residential_phase_3' => self::record(
                    $unavailableReasons,
                    "annual_collections.{$year}.residential_phase_3",
                    $annualMetrics['residential_phase_3'],
                ),
                'commercial_group_1' => self::record(
                    $unavailableReasons,
                    "annual_collections.{$year}.commercial_group_1",
                    $annualMetrics['commercial_group_1'],
                ),
                'commercial_group_2' => self::record(
                    $unavailableReasons,
                    "annual_collections.{$year}.commercial_group_2",
                    $annualMetrics['commercial_group_2'],
                ),
                'total' => self::record($unavailableReasons, "annual_collections.{$year}.total", $yearTotal),
                'cumulative_total' => self::record(
                    $unavailableReasons,
                    "annual_collections.{$year}.cumulative_total",
                    $cumulativeTotal,
                ),
            ];
        }

        $annualCollectionTotal = self::record(
            $unavailableReasons,
            'annual_collection_total',
            $cumulativeTotal ?? self::unavailable('invalid_project_duration'),
        );

        return [$annualCollections, $annualCollectionTotal];
    }

    /**
     * @param  array<string, mixed>|null  $schedule
     * @return array{schedule: array<int, string>|null, reasons: list<string>}
     */
    private static function normalizeScheduleGroup(
        ?array $schedule,
        string $group,
        int $durationYears,
        int $percentScale,
    ): array {
        if (! isset($schedule[$group]) || ! is_array($schedule[$group])) {
            return ['schedule' => null, 'reasons' => ["missing_schedule.{$group}"]];
        }

        $normalizedSchedule = [];
        $reasons = [];

        foreach ($schedule[$group] as $year => $percent) {
            $yearString = (string) $year;

            if (preg_match('/^[1-9][0-9]*$/', $yearString) !== 1) {
                $reasons[] = "invalid_schedule_year.{$group}.{$yearString}";

                continue;
            }

            $normalizedYear = (int) $yearString;

            if (array_key_exists($normalizedYear, $normalizedSchedule)) {
                $reasons[] = "duplicate_schedule_year.{$group}.{$normalizedYear}";

                continue;
            }

            if ($normalizedYear > $durationYears) {
                $reasons[] = "schedule_year_outside_duration.{$group}.{$normalizedYear}";
            }

            if (! is_int($percent) && ! is_float($percent) && ! is_string($percent)) {
                $reasons[] = "invalid_schedule_percentage.{$group}.{$normalizedYear}";

                continue;
            }

            $percentString = trim((string) $percent);

            if (! is_numeric($percentString)) {
                $reasons[] = "invalid_schedule_percentage.{$group}.{$normalizedYear}";

                continue;
            }

            if (DecimalMath::compare($percentString, '0') < 0 || DecimalMath::compare($percentString, '100') > 0) {
                $reasons[] = "schedule_percentage_out_of_range.{$group}.{$normalizedYear}";

                continue;
            }

            $normalizedSchedule[$normalizedYear] = DecimalMath::sum([$percentString], $percentScale);
        }

        for ($year = 1; $year <= $durationYears; $year++) {
            if (! array_key_exists($year, $normalizedSchedule)) {
                $reasons[] = "missing_schedule_year.{$group}.{$year}";
            }
        }

        if ($reasons === [] && DecimalMath::compare(
            DecimalMath::sum(array_values($normalizedSchedule), $percentScale),
            (string) config('aqar_metrics.study_rules.completed_collection_group_total_percent'),
        ) !== 0) {
            $reasons[] = "schedule_total_not_100.{$group}";
        }

        if ($reasons !== []) {
            return ['schedule' => null, 'reasons' => array_values(array_unique($reasons))];
        }

        ksort($normalizedSchedule);

        return ['schedule' => $normalizedSchedule, 'reasons' => []];
    }

    /**
     * @param  array{value: string|null, reasons: list<string>}  $revenueMetric
     * @param  array<int, string>|null  $schedule
     * @param  list<string>  $scheduleReasons
     * @return array<int, array{value: string|null, reasons: list<string>}>
     */
    private static function calculateAnnualGroup(
        array $revenueMetric,
        ?array $schedule,
        array $scheduleReasons,
        int $durationYears,
        int $moneyScale,
    ): array {
        $annualMetrics = [];

        if ($schedule === null) {
            for ($year = 1; $year <= $durationYears; $year++) {
                $annualMetrics[$year] = self::unavailable(...$scheduleReasons);
            }

            return $annualMetrics;
        }

        for ($year = 1; $year <= $durationYears; $year++) {
            if (DecimalMath::isZero($schedule[$year])) {
                $annualMetrics[$year] = self::available(self::zero($moneyScale));

                continue;
            }

            $annualMetrics[$year] = self::percentage(
                $revenueMetric,
                self::available($schedule[$year]),
                $moneyScale,
            );
        }

        if ($revenueMetric['value'] === null) {
            return $annualMetrics;
        }

        $distributedTotal = DecimalMath::sum(
            array_map(static fn (array $metric): string => $metric['value'], $annualMetrics),
            $moneyScale,
        );
        $roundingDifference = DecimalMath::subtract($revenueMetric['value'], $distributedTotal, $moneyScale);

        if (! DecimalMath::isZero($roundingDifference)) {
            for ($year = $durationYears; $year >= 1; $year--) {
                if (! DecimalMath::isZero($schedule[$year])) {
                    $annualMetrics[$year] = self::available(DecimalMath::add(
                        $annualMetrics[$year]['value'],
                        $roundingDifference,
                        $moneyScale,
                    ));

                    break;
                }
            }
        }

        return $annualMetrics;
    }
}
