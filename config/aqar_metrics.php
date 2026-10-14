<?php

use App\Enums\CalculationVersion;

return [

    'calculation' => [
        'default_version' => CalculationVersion::FLEXIBLE_PHASES_V2->value,
        'source_compatibility_version' => CalculationVersion::EXCEL_SOURCE_V1->value,
        'money_scale' => 2,
        'area_scale' => 4,
        'percent_scale' => 2,
        'rounding' => 'half_up',
    ],

    'project' => [
        'default_duration_years' => 8,
        'default_building_coverage_percent' => '60.00',
        'residential_phase_count' => 3,
        'residential_phase_distribution' => [
            'phase_1' => '33.3333333333',
            'phase_2' => '33.3333333333',
            'phase_3' => '33.3333333334',
        ],
        'currencies' => [
            'EGP' => 'الجنيه المصري',
            'SAR' => 'الريال السعودي',
            'AED' => 'الدرهم الإماراتي',
            'USD' => 'الدولار الأمريكي',
        ],
    ],

    'analysis' => [
        'sensitivity_fields' => [
            'land_cost',
            'residential_phase_1_price_per_sqm',
            'residential_phase_2_price_per_sqm',
            'residential_phase_3_price_per_sqm',
            'residential_construction_cost_per_sqm',
            'marketing_percent',
            'sales_percent',
            'administration_percent',
        ],
        'sensitivity_change_limit' => 100,
        'comparison_limit' => 6,
    ],

    'study_rules' => [
        'land_cost' => 'total_amount_counted_once',
        'hotel_revenue' => 'explicit_total_not_distributed_annually',
        'marketing_sales_administration_base' => 'residential_and_commercial_revenue_only',
        'residential_construction_area' => 'maximum_buildable_area',
        'residential_sales_area' => 'developer_share_of_maximum_buildable_area',
        'commercial_and_hotel_areas' => 'independent_from_residential_sales_area',
        'duration_extension' => 'preserve_existing_percentages_and_append_zero_years',
        'duration_reduction' => 'require_manual_redistribution_when_removed_years_are_positive',
        'completed_collection_group_total_percent' => '100',
    ],

    'input_fields' => [
        'developer_share_percent' => ['unit' => 'percent'],
        'land_area' => ['unit' => 'sqm'],
        'land_cost' => ['unit' => 'money'],
        'residential_phase_1_price_per_sqm' => ['unit' => 'money_per_sqm'],
        'residential_phase_2_price_per_sqm' => ['unit' => 'money_per_sqm'],
        'residential_phase_3_price_per_sqm' => ['unit' => 'money_per_sqm'],
        'commercial_sales_1_area' => ['unit' => 'sqm'],
        'commercial_sales_1_price_per_sqm' => ['unit' => 'money_per_sqm'],
        'commercial_sales_2_area' => ['unit' => 'sqm'],
        'commercial_sales_2_price_per_sqm' => ['unit' => 'money_per_sqm'],
        'hotel_construction_area' => ['unit' => 'sqm'],
        'hotel_construction_cost_per_sqm' => ['unit' => 'money_per_sqm'],
        'residential_construction_cost_per_sqm' => ['unit' => 'money_per_sqm'],
        'commercial_construction_area' => ['unit' => 'sqm'],
        'commercial_construction_cost_per_sqm' => ['unit' => 'money_per_sqm'],
        'garage_construction_area' => ['unit' => 'sqm'],
        'garage_construction_cost_per_sqm' => ['unit' => 'money_per_sqm'],
        'infrastructure_landscape_area' => ['unit' => 'sqm'],
        'infrastructure_landscape_cost_per_sqm' => ['unit' => 'money_per_sqm'],
        'marketing_percent' => ['unit' => 'percent'],
        'sales_percent' => ['unit' => 'percent'],
        'administration_percent' => ['unit' => 'percent'],
        'design_supervision_percent' => ['unit' => 'percent'],
        'hotel_revenue' => ['unit' => 'money'],
    ],

    'collection_schedule' => [
        'residential_phase_1' => [1 => '25', 2 => '15', 3 => '15', 4 => '25', 5 => '10', 6 => '10', 7 => '0', 8 => '0'],
        'residential_phase_2' => [1 => '0', 2 => '25', 3 => '15', 4 => '15', 5 => '25', 6 => '10', 7 => '10', 8 => '0'],
        'residential_phase_3' => [1 => '0', 2 => '0', 3 => '25', 4 => '15', 5 => '15', 6 => '25', 7 => '10', 8 => '10'],
        'commercial_group_1' => [1 => '0', 2 => '20', 3 => '15', 4 => '15', 5 => '15', 6 => '20', 7 => '15', 8 => '0'],
        'commercial_group_2' => [1 => '0', 2 => '0', 3 => '20', 4 => '15', 5 => '15', 6 => '15', 7 => '20', 8 => '15'],
    ],
];
