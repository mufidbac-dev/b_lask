<?php

namespace Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Personal\Models\Reminder;

class ReminderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any-reminder');
    }

    public function view(User $user, Reminder $reminder): bool
    {
        return $user->can('view-reminder') && $reminder->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-reminder');
    }

    public function update(User $user, Reminder $reminder): bool
    {
        return $user->can('update-reminder') && $reminder->user_id === $user->id;
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        return $user->can('delete-reminder') && $reminder->user_id === $user->id;
    }

    public function restore(User $user, Reminder $reminder): bool
    {
        return $user->can('restore-reminder');
    }

    public function forceDelete(User $user, Reminder $reminder): bool
    {
        return $user->can('force-delete-reminder');
    }

    public function export(User $user): bool
    {
        return $user->can('export-reminder');
    }
}
