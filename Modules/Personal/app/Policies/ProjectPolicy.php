<?php

namespace Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Personal\Models\Project;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any-project');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('view-project') && $project->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-project');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('update-project') && $project->user_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('delete-project') && $project->user_id === $user->id;
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->can('restore-project');
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->can('force-delete-project');
    }

    public function export(User $user): bool
    {
        return $user->can('export-project');
    }
}
