<?php

namespace Modules\Personal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Personal\Http\Requests\StoreProjectRequest;
use Modules\Personal\Http\Requests\UpdateProjectRequest;
use Modules\Personal\Http\Resources\ProjectResource;
use Modules\Personal\Models\Project;
use Modules\Personal\Services\ProjectService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $service,
    ) {
        $this->authorizeResource(Project::class, 'project');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->service->paginate($request);

        return ProjectResource::collection($items);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return (new ProjectResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        $this->service->loadRelations($project);

        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $item = $this->service->update($project, $request->validated());

        return new ProjectResource($item);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->service->delete($project);

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Project::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = $this->service->bulkDelete($request->ids);

        return response()->json([
            'message' => "{$deleted} Project(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Project::class);

        return $this->service->export($request);
    }
}
