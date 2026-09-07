<?php

namespace Modules\Personal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Personal\Http\Requests\StoreTagRequest;
use Modules\Personal\Http\Requests\UpdateTagRequest;
use Modules\Personal\Http\Resources\TagResource;
use Modules\Personal\Models\Tag;
use Modules\Personal\Services\TagService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TagController extends Controller
{
    public function __construct(
        private readonly TagService $service,
    ) {
        $this->authorizeResource(Tag::class, 'tag');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->service->paginate($request);

        return TagResource::collection($items);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return (new TagResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Tag $tag): TagResource
    {
        $this->service->loadRelations($tag);

        return new TagResource($tag);
    }

    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $item = $this->service->update($tag, $request->validated());

        return new TagResource($item);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->service->delete($tag);

        return response()->json(['message' => 'Tag deleted successfully.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Tag::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = $this->service->bulkDelete($request->ids);

        return response()->json([
            'message' => "{$deleted} Tag(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Tag::class);

        return $this->service->export($request);
    }
}
