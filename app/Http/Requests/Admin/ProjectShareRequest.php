<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProjectShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('share', $project) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where('is_active', true),
            ],
        ];
    }
}
