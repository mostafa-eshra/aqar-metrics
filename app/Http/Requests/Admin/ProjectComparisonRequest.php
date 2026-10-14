<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ProjectComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'projects' => [
                'nullable',
                'array',
                'min:2',
                'max:'.config('aqar_metrics.analysis.comparison_limit'),
            ],
            'projects.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
