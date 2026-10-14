<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\User;

final class ProjectSharingService
{
    public static function share(Project $project, User $viewer): void
    {
        if ((int) $project->user_id === (int) $viewer->getKey()) {
            return;
        }

        $project->viewers()->syncWithoutDetaching([$viewer->getKey()]);
    }

    public static function stopSharing(Project $project, User $viewer): void
    {
        $project->viewers()->detach($viewer->getKey());
    }
}
