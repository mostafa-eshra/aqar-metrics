<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalculationVersion;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCollectionScheduleService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $schedules = ProjectCollectionScheduleService::defaults(8);

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'location' => fake()->city(),
            'currency_code' => 'EGP',
            'project_duration_years' => 8,
            'status' => ProjectStatus::COMPLETED,
            'has_hotel' => false,
            'building_coverage_percent' => '60',
            'calculation_version' => CalculationVersion::CORRECTED_V1,
            'developer_share_percent' => '60',
            'land_area' => '69000',
            'land_cost' => '0',
            'residential_phase_1_price_per_sqm' => '80000',
            'residential_phase_2_price_per_sqm' => '95000',
            'residential_phase_3_price_per_sqm' => '120000',
            'commercial_sales_1_area' => '0',
            'commercial_sales_1_price_per_sqm' => '150000',
            'commercial_sales_2_area' => '0',
            'commercial_sales_2_price_per_sqm' => '200000',
            'hotel_construction_area' => '0',
            'hotel_construction_cost_per_sqm' => '120000',
            'residential_construction_cost_per_sqm' => '25000',
            'commercial_construction_area' => '0',
            'commercial_construction_cost_per_sqm' => '30000',
            'garage_construction_area' => '0',
            'garage_construction_cost_per_sqm' => '15000',
            'infrastructure_landscape_area' => '41400',
            'infrastructure_landscape_cost_per_sqm' => '5000',
            'marketing_percent' => '4',
            'sales_percent' => '5',
            'administration_percent' => '4',
            'design_supervision_percent' => '2.5',
            'hotel_revenue' => '0',
            ...$schedules,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::DRAFT,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::ARCHIVED,
        ]);
    }
}
