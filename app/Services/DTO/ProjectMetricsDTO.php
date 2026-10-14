<?php

declare(strict_types=1);

namespace App\Services\DTO;

use App\Enums\CalculationVersion;

final readonly class ProjectMetricsDTO
{
    /**
     * @param  array{
     *     maximum_buildable_area: ?string,
     *     developer_saleable_area: ?string,
     *     residential_phase_1_area: ?string,
     *     residential_phase_2_area: ?string,
     *     residential_phase_3_area: ?string
     * }  $areas
     * @param  array{
     *     residential_phase_1: ?string,
     *     residential_phase_2: ?string,
     *     residential_phase_3: ?string,
     *     commercial_group_1: ?string,
     *     commercial_group_2: ?string,
     *     residential_total: ?string,
     *     commercial_total: ?string,
     *     residential_and_commercial_total: ?string,
     *     hotel: ?string,
     *     total_with_hotel: ?string
     * }  $revenues
     * @param  array{
     *     hotel: ?string,
     *     residential: ?string,
     *     commercial: ?string,
     *     garage: ?string,
     *     infrastructure_landscape: ?string,
     *     total: ?string
     * }  $constructionCosts
     * @param  array{
     *     marketing: ?string,
     *     sales: ?string,
     *     administration: ?string,
     *     design_supervision: ?string,
     *     land: ?string,
     *     total_including_land: ?string
     * }  $expenses
     * @param  array{total_cost: ?string, estimated_profit: ?string}  $totals
     * @param  array{profit_margin_percent: ?string, return_on_cost_percent: ?string}  $indicators
     * @param  array{
     *     residential_phase_1: ?string,
     *     residential_phase_2: ?string,
     *     residential_phase_3: ?string,
     *     commercial_group_1: ?string,
     *     commercial_group_2: ?string
     * }  $revenueContributionPercent
     * @param  list<array{
     *     year: int,
     *     residential_phase_1: ?string,
     *     residential_phase_2: ?string,
     *     residential_phase_3: ?string,
     *     commercial_group_1: ?string,
     *     commercial_group_2: ?string,
     *     total: ?string,
     *     cumulative_total: ?string
     * }>  $annualCollections
     * @param  array<string, list<string>>  $unavailableReasons
     * @param  list<string>  $calculationNotes
     */
    public function __construct(
        public CalculationVersion $calculationVersion,
        public int $projectDurationYears,
        public int $landCostOccurrences,
        public array $areas,
        public array $revenues,
        public array $constructionCosts,
        public array $expenses,
        public array $totals,
        public array $indicators,
        public array $revenueContributionPercent,
        public array $annualCollections,
        public ?string $annualCollectionTotal,
        public array $unavailableReasons,
        public array $calculationNotes,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'calculation_version' => $this->calculationVersion->value,
            'project_duration_years' => $this->projectDurationYears,
            'land_cost_occurrences' => $this->landCostOccurrences,
            'areas' => $this->areas,
            'revenues' => $this->revenues,
            'construction_costs' => $this->constructionCosts,
            'expenses' => $this->expenses,
            'totals' => $this->totals,
            'indicators' => $this->indicators,
            'revenue_contribution_percent' => $this->revenueContributionPercent,
            'annual_collections' => $this->annualCollections,
            'annual_collection_total' => $this->annualCollectionTotal,
            'unavailable_reasons' => $this->unavailableReasons,
            'calculation_notes' => $this->calculationNotes,
        ];
    }
}
