<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('admin.users.create')
            ? PermissionEnum::USER_CREATE
            : PermissionEnum::USER_EDIT;

        return $this->user()?->can($permission->value) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        $user = $this->route('user');
        $isCreate = $this->routeIs('admin.users.create');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user instanceof User ? $user : null),
            ],
            'password' => [
                $isCreate ? 'required' : 'nullable',
                'max:255',
                'confirmed',
                Password::min(8),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return trans('app.users.attributes');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $this->merge([
            'email' => Str::lower($this->string('email')->trim()->toString()),
        ]);
    }
}
