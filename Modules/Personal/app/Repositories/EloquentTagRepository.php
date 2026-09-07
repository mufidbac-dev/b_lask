<?php

namespace Modules\Personal\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Personal\Models\Tag\Tag;
use Modules\Personal\Repositories\Contracts\TagRepositoryInterface;

class EloquentTagRepository implements TagRepositoryInterface
{
    public function all(): Collection
    {
        return Tag::all();
    }

    public function find(int|string $id): ?Tag
    {
        return Tag::find($id);
    }

    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    public function update(int|string $id, array $data): Tag
    {
        $model = Tag::findOrFail($id);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = Tag::findOrFail($id);

        return (bool) $model->delete();
    }
}
