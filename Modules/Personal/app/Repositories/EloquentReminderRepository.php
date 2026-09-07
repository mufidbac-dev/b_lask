<?php

namespace Modules\Personal\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Personal\Models\Reminder\Reminder;
use Modules\Personal\Repositories\Contracts\ReminderRepositoryInterface;

class EloquentReminderRepository implements ReminderRepositoryInterface
{
    public function all(): Collection
    {
        return Reminder::all();
    }

    public function find(int|string $id): ?Reminder
    {
        return Reminder::find($id);
    }

    public function create(array $data): Reminder
    {
        return Reminder::create($data);
    }

    public function update(int|string $id, array $data): Reminder
    {
        $model = Reminder::findOrFail($id);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = Reminder::findOrFail($id);

        return (bool) $model->delete();
    }
}
