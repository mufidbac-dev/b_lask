<?php

namespace Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Personal\Models\Tag;

class TagPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any-tag');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can('view-tag') && $tag->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-tag');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('update-tag') && $tag->user_id === $user->id;
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('delete-tag') && $tag->user_id === $user->id;
    }

    public function restore(User $user, Tag $tag): bool
    {
        return $user->can('restore-tag');
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        return $user->can('force-delete-tag');
    }

    public function export(User $user): bool
    {
        return $user->can('export-tag');
    }
}
