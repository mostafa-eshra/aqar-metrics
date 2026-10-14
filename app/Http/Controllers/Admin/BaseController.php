<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\Middleware;

abstract class BaseController extends Controller
{
    /** @param list<string> $only */
    protected static function setPermissionToController(PermissionEnum $permission, array $only): Middleware
    {
        return new Middleware("can:{$permission->value}", only: $only);
    }
}
