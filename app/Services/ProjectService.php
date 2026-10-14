<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Services\DTO\ProjectMetricsDTO;
use Illuminate\Support\Arr;

final class ProjectService
{
    /** @param array<string, mixed> $data */
    public static function create(User $owner, array $data): Project
    {
        $project = new Project;
        $attributes = self::persistableAttributes($project, $data);
        $durationYears = (int) ($attributes['project_duration_years'] ?? config('aqar_metrics.project.default_duration_years'));
        $defaultSchedules = ProjectCollectionScheduleService::defaults($durationYears);

        $attributes['residential_collection_schedule'] ??= $defaultSchedules['residential_collection_schedule'];
        $attributes['commercial_collection_schedule'] ??= $defaultSchedules['commercial_collection_schedule'];

        self::applyHotelChoice($attributes);

        return ProjectRepository::createForOwner(
            owner: $owner,
            attributes: $attributes,
            calculationVersion: config('aqar_metrics.calculation.default_version'),
        );
    }

    /** @param array<string, mixed> $data */
    public static function update(Project $project, array $data): Project
    {
        $attributes = self::persistableAttributes($project, $data);
        self::applyHotelChoice($attributes);

        return ProjectRepository::update($project, $attributes);
    }

    /** @param array<string, mixed> $data */
    public static function preview(User $owner, array $data): ProjectMetricsDTO
    {
        $project = new Project;
        $attributes = self::persistableAttributes($project, $data);
        $durationYears = (int) ($attributes['project_duration_years'] ?? config('aqar_metrics.project.default_duration_years'));
        $defaultSchedules = ProjectCollectionScheduleService::defaults($durationYears);

        $attributes['residential_collection_schedule'] ??= $defaultSchedules['residential_collection_schedule'];
        $attributes['commercial_collection_schedule'] ??= $defaultSchedules['commercial_collection_schedule'];

        self::applyHotelChoice($attributes);

        $project->fill($attributes);
        $project->owner()->associate($owner);
        $project->setAttribute('calculation_version', config('aqar_metrics.calculation.default_version'));

        return ProjectCalculationService::calculate($project);
    }

    public static function duplicate(Project $project, string $name): Project
    {
        $duplicate = $project->replicate();
        $duplicate->name = $name;
        $duplicate->status = ProjectStatus::DRAFT;
        $duplicate->save();

        return $duplicate->refresh();
    }

    public static function archive(Project $project): Project
    {
        $project->status = ProjectStatus::ARCHIVED;
        $project->save();

        return $project->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function persistableAttributes(Project $project, array $data): array
    {
        $attributes = Arr::only($data, $project->getFillable());

        if (array_key_exists('project_duration_years', $attributes)) {
            $attributes['project_duration_years'] = (int) $attributes['project_duration_years'];
        }

        if (array_key_exists('has_hotel', $attributes)) {
            $attributes['has_hotel'] = (bool) $attributes['has_hotel'];
        }

        return $attributes;
    }

    /** @param array<string, mixed> $attributes */
    private static function applyHotelChoice(array &$attributes): void
    {
        if (array_key_exists('has_hotel', $attributes) && $attributes['has_hotel'] === false) {
            $attributes['hotel_construction_area'] = '0';
            $attributes['hotel_revenue'] = '0';
        }
    }
}
