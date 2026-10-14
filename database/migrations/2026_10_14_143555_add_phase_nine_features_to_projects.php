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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('calculation_version')->default('flexible-phases-v2')->change();
            $table->foreignId('parent_project_id')
                ->nullable()
                ->after('user_id')
                ->constrained('projects')
                ->nullOnDelete();
            $table->string('scenario_name')->nullable()->after('name');
            $table->json('residential_phase_distribution')->nullable()->after('developer_share_percent');
            $table->decimal('discount_rate_percent', 7, 4)->nullable()->after('hotel_revenue');
            $table->json('annual_cash_flow_schedule')->nullable()->after('discount_rate_percent');

            $table->index(['parent_project_id', 'updated_at']);
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['project_id', 'user_id']);
            $table->index(['user_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');

        Schema::table('projects', function (Blueprint $table) {
            $table->string('calculation_version')->default('corrected-v1')->change();
            $table->dropForeign(['parent_project_id']);
            $table->dropIndex(['parent_project_id', 'updated_at']);
            $table->dropColumn([
                'parent_project_id',
                'scenario_name',
                'residential_phase_distribution',
                'discount_rate_percent',
                'annual_cash_flow_schedule',
            ]);
        });
    }
};
