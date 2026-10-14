<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case USER_LIST = 'users.list';
    case USER_CREATE = 'users.create';
    case USER_EDIT = 'users.edit';
    case PROJECT_LIST = 'projects.list';
    case PROJECT_VIEW = 'projects.view';
    case PROJECT_CREATE = 'projects.create';
    case PROJECT_EDIT = 'projects.edit';
    case PROJECT_DUPLICATE = 'projects.duplicate';
    case PROJECT_ARCHIVE = 'projects.archive';
    case PROJECT_PREVIEW = 'projects.preview';
    case PROJECT_ANALYZE = 'projects.analyze';
    case PROJECT_COMPARE = 'projects.compare';
    case PROJECT_EXPORT = 'projects.export';
    case PROJECT_SHARE = 'projects.share';
    case PROJECT_SCENARIO = 'projects.scenario';
}
