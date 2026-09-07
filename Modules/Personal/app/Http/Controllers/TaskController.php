<?php

namespace Modules\Personal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Personal\Http\Requests\StoreTaskRequest;
use Modules\Personal\Http\Requests\UpdateTaskRequest;
use Modules\Personal\Http\Resources\TaskResource;
use Modules\Personal\Models\Task;
use Modules\Personal\Services\TaskService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $service,
    ) {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->service->paginate($request);

        return TaskResource::collection($items);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return (new TaskResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        $this->service->loadRelations($task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $item = $this->service->update($task, $request->validated());

        return new TaskResource($item);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->service->delete($task);

        return response()->json(['message' => 'Task deleted successfully.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Task::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = $this->service->bulkDelete($request->ids);

        return response()->json([
            'message' => "{$deleted} Task(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Task::class);

        return $this->service->export($request);
    }
}
