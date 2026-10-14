<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case USER_LIST = 'users.list';
    case USER_CREATE = 'users.create';
    case USER_EDIT = 'users.edit';
}
