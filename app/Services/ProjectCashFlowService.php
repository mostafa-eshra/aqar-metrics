<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Services\DTO\ProjectCashFlowDTO;
use App\Services\DTO\ProjectMetricsDTO;
use App\Support\DecimalMath;

final class ProjectCashFlowService
{
    /** @var list<string> */
    public const INPUT_FIELDS = [
        'other_revenue',
        'project_cost',
        'financing_inflow',
        'financing_cost',
        'taxes',
    ];

    /** @return array<int, array<string, string>> */
    public static function defaults(int $durationYears): array
    {
        $schedule = [];

        for ($year = 0; $year <= $durationYears; $year++) {
            $schedule[$year] = array_fill_keys(self::INPUT_FIELDS, '0');
        }

        return $schedule;
    }

    public static function calculate(Project $project, ?ProjectMetricsDTO $metrics = null): ProjectCashFlowDTO
    {
        $metrics ??= ProjectCalculationService::calculate($project);
        $schedule = $project->annual_cash_flow_schedule;
        $discountRate = $project->discount_rate_percent;

        if (! is_array($schedule)) {
            return new ProjectCashFlowDTO([], null, null, null, ['missing_cash_flow_schedule']);
        }

        $rows = [];
        $cashFlows = [];
        $cumulative = '0.00';
        $collectionByYear = collect($metrics->annualCollections)->keyBy('year');

        for ($year = 0; $year <= $project->project_duration_years; $year++) {
            $yearInputs = $schedule[$year] ?? $schedule[(string) $year] ?? null;

            if (! is_array($yearInputs)) {
                return new ProjectCashFlowDTO([], null, null, null, ["missing_cash_flow_year.{$year}"]);
            }

            $values = [];

            foreach (self::INPUT_FIELDS as $field) {
                $value = $yearInputs[$field] ?? null;

                if ((! is_string($value) && ! is_int($value) && ! is_float($value))
                    || ! is_numeric((string) $value)
                    || DecimalMath::compare((string) $value, '0') < 0) {
                    return new ProjectCashFlowDTO([], null, null, null, ["invalid_cash_flow_value.{$year}.{$field}"]);
                }

                $values[$field] = DecimalMath::sum([(string) $value]);
            }

            $collectionRow = $year === 0 ? null : $collectionByYear->get($year);

            if ($year > 0 && (! is_array($collectionRow) || $collectionRow['total'] === null)) {
                return new ProjectCashFlowDTO([], null, null, null, ['annual_collections_unavailable']);
            }

            $collections = $year === 0 ? '0.00' : (string) $collectionRow['total'];
            $inflows = DecimalMath::sum([
                $collections,
                $values['other_revenue'],
                $values['financing_inflow'],
            ]);
            $outflows = DecimalMath::sum([
                $values['project_cost'],
                $values['financing_cost'],
                $values['taxes'],
            ]);
            $netCashFlow = DecimalMath::subtract($inflows, $outflows);
            $cumulative = DecimalMath::add($cumulative, $netCashFlow);
            $cashFlows[] = (float) $netCashFlow;
            $rows[] = [
                'year' => $year,
                'collections' => $collections,
                ...$values,
                'net_cash_flow' => $netCashFlow,
                'cumulative_cash_flow' => $cumulative,
            ];
        }

        $reasons = [];
        $netPresentValue = null;

        if ($discountRate === null) {
            $reasons[] = 'missing_discount_rate';
        } else {
            $netPresentValue = self::netPresentValue($cashFlows, (float) $discountRate);
        }

        $internalRateOfReturn = self::internalRateOfReturn($cashFlows);

        if ($internalRateOfReturn === null) {
            $reasons[] = 'irr_requires_positive_and_negative_cash_flows';
        }

        $paybackPeriod = self::paybackPeriod($cashFlows);

        if ($paybackPeriod === null) {
            $reasons[] = 'cash_flows_do_not_reach_payback';
        }

        return new ProjectCashFlowDTO(
            annualRows: $rows,
            netPresentValue: $netPresentValue,
            internalRateOfReturnPercent: $internalRateOfReturn,
            paybackPeriodYears: $paybackPeriod,
            unavailableReasons: $reasons,
        );
    }

    /** @param list<float> $cashFlows */
    private static function netPresentValue(array $cashFlows, float $discountRatePercent): string
    {
        $rate = $discountRatePercent / 100;
        $value = 0.0;

        foreach ($cashFlows as $year => $cashFlow) {
            $value += $cashFlow / ((1 + $rate) ** $year);
        }

        return number_format($value, 2, '.', '');
    }

    /** @param list<float> $cashFlows */
    private static function internalRateOfReturn(array $cashFlows): ?string
    {
        if (! collect($cashFlows)->contains(static fn (float $value): bool => $value < 0)
            || ! collect($cashFlows)->contains(static fn (float $value): bool => $value > 0)) {
            return null;
        }

        $nonZeroCashFlows = array_values(array_filter(
            $cashFlows,
            static fn (float $value): bool => abs($value) > 0.000001,
        ));
        $signChanges = 0;

        for ($index = 1; $index < count($nonZeroCashFlows); $index++) {
            if (($nonZeroCashFlows[$index - 1] < 0) !== ($nonZeroCashFlows[$index] < 0)) {
                $signChanges++;
            }
        }

        if ($signChanges !== 1) {
            return null;
        }

        $low = -0.999999;
        $high = 10.0;
        $lowValue = self::discountedValue($cashFlows, $low);
        $highValue = self::discountedValue($cashFlows, $high);

        while ($lowValue * $highValue > 0 && $high < 1000000) {
            $high *= 10;
            $highValue = self::discountedValue($cashFlows, $high);
        }

        if ($lowValue * $highValue > 0) {
            return null;
        }

        for ($iteration = 0; $iteration < 200; $iteration++) {
            $mid = ($low + $high) / 2;
            $midValue = self::discountedValue($cashFlows, $mid);

            if (abs($midValue) < 0.000001) {
                return number_format($mid * 100, 2, '.', '');
            }

            if ($lowValue * $midValue <= 0) {
                $high = $mid;
            } else {
                $low = $mid;
                $lowValue = $midValue;
            }
        }

        return number_format((($low + $high) / 2) * 100, 2, '.', '');
    }

    /** @param list<float> $cashFlows */
    private static function discountedValue(array $cashFlows, float $rate): float
    {
        $value = 0.0;

        foreach ($cashFlows as $year => $cashFlow) {
            $value += $cashFlow / ((1 + $rate) ** $year);
        }

        return $value;
    }

    /** @param list<float> $cashFlows */
    private static function paybackPeriod(array $cashFlows): ?string
    {
        $cumulative = 0.0;
        $hasNegativeBalance = false;

        foreach ($cashFlows as $year => $cashFlow) {
            $previous = $cumulative;
            $cumulative += $cashFlow;
            $hasNegativeBalance = $hasNegativeBalance || $previous < 0 || $cumulative < 0;

            if ($hasNegativeBalance && $previous < 0 && $cumulative >= 0 && $cashFlow > 0) {
                return number_format(($year - 1) + (abs($previous) / $cashFlow), 2, '.', '');
            }
        }

        return null;
    }
}
