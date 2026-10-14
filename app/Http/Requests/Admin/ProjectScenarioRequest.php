<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class ProjectScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('createScenario', $project) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'scenario_name' => ['required', 'string', 'max:100'],
        ];
    }
}
