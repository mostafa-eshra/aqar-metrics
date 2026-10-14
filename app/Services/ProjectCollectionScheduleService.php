<?php

declare(strict_types=1);

namespace App\Services;

final class ProjectCollectionScheduleService
{
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
        $defaultDurationYears = (int) config('aqar_metrics.project.default_duration_years');

        $residentialSchedule = [
            'residential_phase_1' => $configuredSchedule['residential_phase_1'],
            'residential_phase_2' => $configuredSchedule['residential_phase_2'],
            'residential_phase_3' => $configuredSchedule['residential_phase_3'],
        ];
        $commercialSchedule = [
            'commercial_group_1' => $configuredSchedule['commercial_group_1'],
            'commercial_group_2' => $configuredSchedule['commercial_group_2'],
        ];

        if ($durationYears > $defaultDurationYears) {
            for ($year = $defaultDurationYears + 1; $year <= $durationYears; $year++) {
                foreach ($residentialSchedule as &$schedule) {
                    $schedule[$year] = '0';
                }

                unset($schedule);

                foreach ($commercialSchedule as &$schedule) {
                    $schedule[$year] = '0';
                }

                unset($schedule);
            }
        }

        return [
            'residential_collection_schedule' => $residentialSchedule,
            'commercial_collection_schedule' => $commercialSchedule,
        ];
    }
}
