<?php

namespace Modules\Personal\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Personal\Models\Note\Note;
use Modules\Personal\Repositories\Contracts\NoteRepositoryInterface;

class EloquentNoteRepository implements NoteRepositoryInterface
{
    public function all(): Collection
    {
        return Note::all();
    }

    public function find(int|string $id): ?Note
    {
        return Note::find($id);
    }

    public function create(array $data): Note
    {
        return Note::create($data);
    }

    public function update(int|string $id, array $data): Note
    {
        $model = Note::findOrFail($id);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = Note::findOrFail($id);

        return (bool) $model->delete();
    }
}
