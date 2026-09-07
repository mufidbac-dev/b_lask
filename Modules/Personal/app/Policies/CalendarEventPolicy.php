<?php

namespace Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Personal\Models\CalendarEvent;

class CalendarEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any-calendar-event');
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('view-calendar-event') && $calendarEvent->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-calendar-event');
    }

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('update-calendar-event') && $calendarEvent->user_id === $user->id;
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('delete-calendar-event') && $calendarEvent->user_id === $user->id;
    }

    public function restore(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('restore-calendar-event');
    }

    public function forceDelete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('force-delete-calendar-event');
    }

    public function export(User $user): bool
    {
        return $user->can('export-calendar-event');
    }
}
