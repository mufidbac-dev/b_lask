<?php

namespace Modules\Personal\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personal\Exports\ProjectExport;
use Modules\Personal\Models\Project;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectService
{
    /** Kolom yang dicari saat ada parameter ?search=… */
    protected array $searchable = [
        // 'name', 'code',
    ];

    /** Relasi yang selalu di-eager-load pada show(). */
    protected array $relations = [
        // 'relatedModel',
    ];

    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = Project::query()->where('user_id', $request->user()->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if ($sortBy = $request->input('sort_by', 'created_at')) {
            $direction = $request->input('sort_dir', 'desc');
            $query->orderBy($sortBy, $direction);
        }

        return $query->paginate($request->input('per_page', 15));
    }

    public function create(array $data): Project
    {
        return Project::create([...$data, 'user_id' => auth()->id()]);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return (bool) $project->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Project::where('user_id', auth()->id())->whereIn('id', $ids)->delete();
    }

    public function loadRelations(Project $project): void
    {
        if (! empty($this->relations)) {
            $project->loadMissing($this->relations);
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new ProjectExport($request), 'projects.xlsx');
    }
}
