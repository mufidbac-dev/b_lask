<?php

namespace Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Personal\Models\Note;

class NotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any-note');
    }

    public function view(User $user, Note $note): bool
    {
        return $user->can('view-note') && $note->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-note');
    }

    public function update(User $user, Note $note): bool
    {
        return $user->can('update-note') && $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->can('delete-note') && $note->user_id === $user->id;
    }

    public function restore(User $user, Note $note): bool
    {
        return $user->can('restore-note');
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $user->can('force-delete-note');
    }

    public function export(User $user): bool
    {
        return $user->can('export-note');
    }
}
