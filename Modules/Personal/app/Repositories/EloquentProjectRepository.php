<?php

namespace Modules\Personal\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Personal\Models\Project\Project;
use Modules\Personal\Repositories\Contracts\ProjectRepositoryInterface;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::all();
    }

    public function find(int|string $id): ?Project
    {
        return Project::find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(int|string $id, array $data): Project
    {
        $model = Project::findOrFail($id);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = Project::findOrFail($id);

        return (bool) $model->delete();
    }
}
