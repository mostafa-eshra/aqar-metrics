<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalculationVersion;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'scenario_name',
    'description',
    'location',
    'currency_code',
    'project_duration_years',
    'status',
    'has_hotel',
    'building_coverage_percent',
    'developer_share_percent',
    'residential_phase_distribution',
    'land_area',
    'land_cost',
    'residential_phase_1_price_per_sqm',
    'residential_phase_2_price_per_sqm',
    'residential_phase_3_price_per_sqm',
    'commercial_sales_1_area',
    'commercial_sales_1_price_per_sqm',
    'commercial_sales_2_area',
    'commercial_sales_2_price_per_sqm',
    'hotel_construction_area',
    'hotel_construction_cost_per_sqm',
    'residential_construction_cost_per_sqm',
    'commercial_construction_area',
    'commercial_construction_cost_per_sqm',
    'garage_construction_area',
    'garage_construction_cost_per_sqm',
    'infrastructure_landscape_area',
    'infrastructure_landscape_cost_per_sqm',
    'marketing_percent',
    'sales_percent',
    'administration_percent',
    'design_supervision_percent',
    'hotel_revenue',
    'discount_rate_percent',
    'annual_cash_flow_schedule',
    'residential_collection_schedule',
    'commercial_collection_schedule',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /** @var list<string> */
    public const MONEY_FIELDS = [
        'land_cost',
        'residential_phase_1_price_per_sqm',
        'residential_phase_2_price_per_sqm',
        'residential_phase_3_price_per_sqm',
        'commercial_sales_1_price_per_sqm',
        'commercial_sales_2_price_per_sqm',
        'hotel_construction_cost_per_sqm',
        'residential_construction_cost_per_sqm',
        'commercial_construction_cost_per_sqm',
        'garage_construction_cost_per_sqm',
        'infrastructure_landscape_cost_per_sqm',
        'hotel_revenue',
    ];

    /** @var list<string> */
    public const AREA_FIELDS = [
        'land_area',
        'commercial_sales_1_area',
        'commercial_sales_2_area',
        'hotel_construction_area',
        'commercial_construction_area',
        'garage_construction_area',
        'infrastructure_landscape_area',
    ];

    /** @var list<string> */
    public const PERCENT_FIELDS = [
        'building_coverage_percent',
        'developer_share_percent',
        'marketing_percent',
        'sales_percent',
        'administration_percent',
        'design_supervision_percent',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'project_duration_years' => 8,
        'status' => 'draft',
        'has_hotel' => false,
        'building_coverage_percent' => '60.00',
        'calculation_version' => 'flexible-phases-v2',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Project, $this> */
    public function parentProject(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_project_id');
    }

    /** @return HasMany<Project, $this> */
    public function scenarios(): HasMany
    {
        return $this->hasMany(self::class, 'parent_project_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'project_duration_years' => 'integer',
            'status' => ProjectStatus::class,
            'has_hotel' => 'boolean',
            'calculation_version' => CalculationVersion::class,
            'building_coverage_percent' => 'decimal:2',
            'developer_share_percent' => 'decimal:2',
            'residential_phase_distribution' => 'array',
            'land_area' => 'decimal:4',
            'land_cost' => 'decimal:2',
            'residential_phase_1_price_per_sqm' => 'decimal:2',
            'residential_phase_2_price_per_sqm' => 'decimal:2',
            'residential_phase_3_price_per_sqm' => 'decimal:2',
            'commercial_sales_1_area' => 'decimal:4',
            'commercial_sales_1_price_per_sqm' => 'decimal:2',
            'commercial_sales_2_area' => 'decimal:4',
            'commercial_sales_2_price_per_sqm' => 'decimal:2',
            'hotel_construction_area' => 'decimal:4',
            'hotel_construction_cost_per_sqm' => 'decimal:2',
            'residential_construction_cost_per_sqm' => 'decimal:2',
            'commercial_construction_area' => 'decimal:4',
            'commercial_construction_cost_per_sqm' => 'decimal:2',
            'garage_construction_area' => 'decimal:4',
            'garage_construction_cost_per_sqm' => 'decimal:2',
            'infrastructure_landscape_area' => 'decimal:4',
            'infrastructure_landscape_cost_per_sqm' => 'decimal:2',
            'marketing_percent' => 'decimal:2',
            'sales_percent' => 'decimal:2',
            'administration_percent' => 'decimal:2',
            'design_supervision_percent' => 'decimal:2',
            'hotel_revenue' => 'decimal:2',
            'discount_rate_percent' => 'decimal:4',
            'annual_cash_flow_schedule' => 'array',
            'residential_collection_schedule' => 'array',
            'commercial_collection_schedule' => 'array',
        ];
    }
}
