<?php

namespace Modules\Personal\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Personal\Models\CalendarEvent\CalendarEvent;
use Modules\Personal\Repositories\Contracts\CalendarEventRepositoryInterface;

class EloquentCalendarEventRepository implements CalendarEventRepositoryInterface
{
    public function all(): Collection
    {
        return CalendarEvent::all();
    }

    public function find(int|string $id): ?CalendarEvent
    {
        return CalendarEvent::find($id);
    }

    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::create($data);
    }

    public function update(int|string $id, array $data): CalendarEvent
    {
        $model = CalendarEvent::findOrFail($id);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = CalendarEvent::findOrFail($id);

        return (bool) $model->delete();
    }
}
