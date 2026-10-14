<?php

declare(strict_types=1);

use App\Services\ProjectCollectionScheduleService;
use Tests\TestCase;

uses(TestCase::class);

it('builds reviewable schedules for project durations of 1, 5, 8, and 10 years', function (int $durationYears) {
    $schedules = ProjectCollectionScheduleService::defaults($durationYears);

    foreach ($schedules as $schedule) {
        foreach ($schedule as $yearPercentages) {
            for ($year = 1; $year <= $durationYears; $year++) {
                expect($yearPercentages)->toHaveKey($year);
            }

            foreach ($yearPercentages as $year => $percentage) {
                if ($year > $durationYears) {
                    expect((float) $percentage)->toBeGreaterThan(0);
                }
            }

            expect(array_sum(array_map('floatval', $yearPercentages)))->toBe(100.0);
        }
    }
})->with([
    'one year' => 1,
    'five years' => 5,
    'source eight years' => 8,
    'ten years' => 10,
]);

it('preserves the source percentages and appends zero years when duration increases', function () {
    $eightYearSchedules = ProjectCollectionScheduleService::defaults(8);
    $tenYearSchedules = ProjectCollectionScheduleService::defaults(10);

    foreach ($eightYearSchedules as $scheduleField => $schedule) {
        foreach ($schedule as $group => $yearPercentages) {
            expect(array_slice($tenYearSchedules[$scheduleField][$group], 0, 8, true))
                ->toBe($yearPercentages)
                ->and($tenYearSchedules[$scheduleField][$group][9])->toBe('0')
                ->and($tenYearSchedules[$scheduleField][$group][10])->toBe('0');
        }
    }
});

it('keeps positive excluded years for redistribution and removes excluded zero years', function () {
    $schedule = ProjectCollectionScheduleService::forDuration([
        'residential_phase_1' => [
            1 => '25',
            2 => '15',
            3 => '15',
            4 => '25',
            5 => '10',
            6 => '10',
            7 => '0',
            8 => '0',
        ],
    ], 5);

    expect($schedule['residential_phase_1'])->toBe([
        1 => '25',
        2 => '15',
        3 => '15',
        4 => '25',
        5 => '10',
        6 => '10',
    ]);
});
