<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class UserService
{
    /**
     * @param  array{name: string, email: string, password: string, role: string, is_active: bool|string|int}  $data
     */
    public static function create(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ]);
    }

    /**
     * @param  array{name: string, email: string, password?: ?string, role: string, is_active: bool|string|int}  $data
     */
    public static function update(User $user, array $data): User
    {
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ];

        if (filled($data['password'] ?? null)) {
            $attributes['password'] = $data['password'];
        }

        $user->fill($attributes);
        $user->save();

        return $user->refresh();
    }
}
