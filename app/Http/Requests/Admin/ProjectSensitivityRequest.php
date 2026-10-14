<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProjectSensitivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('analyze', $project) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $limit = (int) config('aqar_metrics.analysis.sensitivity_change_limit');

        return [
            'field' => ['required', Rule::in(config('aqar_metrics.analysis.sensitivity_fields', []))],
            'changes' => ['required', 'array', 'min:2', 'max:9'],
            'changes.*' => ['required', 'integer', "between:-{$limit},{$limit}", 'distinct'],
        ];
    }
}
