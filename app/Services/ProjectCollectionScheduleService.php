<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DecimalMath;

final class ProjectCollectionScheduleService
{
    /** @var list<string> */
    public const array RESIDENTIAL_GROUPS = [
        'residential_phase_1',
        'residential_phase_2',
        'residential_phase_3',
    ];

    /** @var list<string> */
    public const array COMMERCIAL_GROUPS = [
        'commercial_group_1',
        'commercial_group_2',
    ];

    /**
     * @return array{
     *     residential_collection_schedule: array<string, array<int, string>>,
     *     commercial_collection_schedule: array<string, array<int, string>>
     * }
     */
    public static function defaults(int $durationYears): array
    {
        /** @var array<string, array<int, string>> $configuredSchedule */
        $configuredSchedule = config('aqar_metrics.collection_schedule');

        $residentialSchedule = [
            'residential_phase_1' => $configuredSchedule['residential_phase_1'],
            'residential_phase_2' => $configuredSchedule['residential_phase_2'],
            'residential_phase_3' => $configuredSchedule['residential_phase_3'],
        ];
        $commercialSchedule = [
            'commercial_group_1' => $configuredSchedule['commercial_group_1'],
            'commercial_group_2' => $configuredSchedule['commercial_group_2'],
        ];

        return [
            'residential_collection_schedule' => self::forDuration($residentialSchedule, $durationYears),
            'commercial_collection_schedule' => self::forDuration($commercialSchedule, $durationYears),
        ];
    }

    /**
     * Add missing active years as zero, preserve positive excluded years for review,
     * and remove excluded zero years because they carry no revenue to redistribute.
     *
     * @param  array<string, array<int|string, int|float|string>>  $schedule
     * @return array<string, array<int, string>>
     */
    public static function forDuration(array $schedule, int $durationYears): array
    {
        $durationYears = max(1, $durationYears);
        $adjustedSchedule = [];

        foreach ($schedule as $group => $yearPercentages) {
            $adjustedYearPercentages = [];

            foreach ($yearPercentages as $year => $percentage) {
                $normalizedYear = (int) $year;
                $normalizedPercentage = (string) $percentage;

                if ($normalizedYear < 1) {
                    continue;
                }

                if ($normalizedYear > $durationYears && DecimalMath::isZero($normalizedPercentage)) {
                    continue;
                }

                $adjustedYearPercentages[$normalizedYear] = $normalizedPercentage;
            }

            for ($year = 1; $year <= $durationYears; $year++) {
                $adjustedYearPercentages[$year] ??= '0';
            }

            ksort($adjustedYearPercentages);
            $adjustedSchedule[$group] = $adjustedYearPercentages;
        }

        return $adjustedSchedule;
    }
}
