<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Support\DecimalMath;

final class ProjectSensitivityService
{
    /**
     * @param  list<int>  $changes
     * @return list<array<string, string|int|null>>
     */
    public static function analyze(Project $project, string $field, array $changes): array
    {
        if (! in_array($field, config('aqar_metrics.analysis.sensitivity_fields', []), true)) {
            return [];
        }

        $baseValue = $project->getAttribute($field);

        if ($baseValue === null) {
            return [];
        }

        $rows = [];

        foreach ($changes as $changePercent) {
            $temporaryProject = $project->replicate();
            $multiplier = DecimalMath::add('1', DecimalMath::divide((string) $changePercent, '100', 6), 6);
            $temporaryProject->setAttribute($field, DecimalMath::multiply((string) $baseValue, $multiplier));
            $metrics = ProjectCalculationService::calculate($temporaryProject);
            $rows[] = [
                'change_percent' => $changePercent,
                'input_value' => (string) $temporaryProject->getAttribute($field),
                'total_revenue' => $metrics->revenues['total_with_hotel'],
                'total_cost' => $metrics->totals['total_cost'],
                'estimated_profit' => $metrics->totals['estimated_profit'],
                'profit_margin_percent' => $metrics->indicators['profit_margin_percent'],
                'return_on_cost_percent' => $metrics->indicators['return_on_cost_percent'],
            ];
        }

        return $rows;
    }
}
