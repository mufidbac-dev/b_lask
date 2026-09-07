<?php

namespace Modules\Personal\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personal\Exports\TaskExport;
use Modules\Personal\Models\Task;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaskService
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
        $query = Task::query()->where('user_id', $request->user()->id);

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

    public function create(array $data): Task
    {
        return Task::create([...$data, 'user_id' => auth()->id()]);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh();
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Task::where('user_id', auth()->id())->whereIn('id', $ids)->delete();
    }

    public function loadRelations(Task $task): void
    {
        if (! empty($this->relations)) {
            $task->loadMissing($this->relations);
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new TaskExport($request), 'tasks.xlsx');
    }
}
