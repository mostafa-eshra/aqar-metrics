<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRATOR = 'administrator';
    case USER = 'user';

    public function label(): string
    {
        return trans("app.roles.{$this->value}");
    }
}
