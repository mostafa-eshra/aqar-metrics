<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->unsignedSmallInteger('project_duration_years')->default(8);
            $table->string('status')->default('draft');
            $table->boolean('has_hotel')->default(false);
            $table->decimal('building_coverage_percent', 5, 2)->default(60);
            $table->string('calculation_version')->default('corrected-v1');

            $table->decimal('developer_share_percent', 5, 2)->nullable();
            $table->decimal('land_area', 18, 4)->nullable();
            $table->decimal('land_cost', 18, 2)->nullable();
            $table->decimal('residential_phase_1_price_per_sqm', 18, 2)->nullable();
            $table->decimal('residential_phase_2_price_per_sqm', 18, 2)->nullable();
            $table->decimal('residential_phase_3_price_per_sqm', 18, 2)->nullable();
            $table->decimal('commercial_sales_1_area', 18, 4)->nullable();
            $table->decimal('commercial_sales_1_price_per_sqm', 18, 2)->nullable();
            $table->decimal('commercial_sales_2_area', 18, 4)->nullable();
            $table->decimal('commercial_sales_2_price_per_sqm', 18, 2)->nullable();
            $table->decimal('hotel_construction_area', 18, 4)->nullable();
            $table->decimal('hotel_construction_cost_per_sqm', 18, 2)->nullable();
            $table->decimal('residential_construction_cost_per_sqm', 18, 2)->nullable();
            $table->decimal('commercial_construction_area', 18, 4)->nullable();
            $table->decimal('commercial_construction_cost_per_sqm', 18, 2)->nullable();
            $table->decimal('garage_construction_area', 18, 4)->nullable();
            $table->decimal('garage_construction_cost_per_sqm', 18, 2)->nullable();
            $table->decimal('infrastructure_landscape_area', 18, 4)->nullable();
            $table->decimal('infrastructure_landscape_cost_per_sqm', 18, 2)->nullable();
            $table->decimal('marketing_percent', 5, 2)->nullable();
            $table->decimal('sales_percent', 5, 2)->nullable();
            $table->decimal('administration_percent', 5, 2)->nullable();
            $table->decimal('design_supervision_percent', 5, 2)->nullable();
            $table->decimal('hotel_revenue', 18, 2)->nullable();

            $table->json('residential_collection_schedule')->nullable();
            $table->json('commercial_collection_schedule')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
