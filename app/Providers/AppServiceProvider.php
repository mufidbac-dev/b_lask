<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Personal\Models\CalendarEvent;
use Modules\Personal\Models\Note;
use Modules\Personal\Models\Project;
use Modules\Personal\Models\Reminder;
use Modules\Personal\Models\Tag;
use Modules\Personal\Models\Task;
use Modules\Personal\Policies\CalendarEventPolicy;
use Modules\Personal\Policies\NotePolicy;
use Modules\Personal\Policies\ProjectPolicy;
use Modules\Personal\Policies\ReminderPolicy;
use Modules\Personal\Policies\TagPolicy;
use Modules\Personal\Policies\TaskPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(Reminder::class, ReminderPolicy::class);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });
    }
}
