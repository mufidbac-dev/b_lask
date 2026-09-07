<?php

namespace Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Personal\Models\Task;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any-task');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('view-task') && $task->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-task');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('update-task') && $task->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('delete-task') && $task->user_id === $user->id;
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->can('restore-task');
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->can('force-delete-task');
    }

    public function export(User $user): bool
    {
        return $user->can('export-task');
    }
}
