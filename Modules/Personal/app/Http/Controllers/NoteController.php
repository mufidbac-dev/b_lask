<?php

namespace Modules\Personal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Personal\Http\Requests\StoreNoteRequest;
use Modules\Personal\Http\Requests\UpdateNoteRequest;
use Modules\Personal\Http\Resources\NoteResource;
use Modules\Personal\Models\Note;
use Modules\Personal\Services\NoteService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteService $service,
    ) {
        $this->authorizeResource(Note::class, 'note');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->service->paginate($request);

        return NoteResource::collection($items);
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return (new NoteResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Note $note): NoteResource
    {
        $this->service->loadRelations($note);

        return new NoteResource($note);
    }

    public function update(UpdateNoteRequest $request, Note $note): NoteResource
    {
        $item = $this->service->update($note, $request->validated());

        return new NoteResource($item);
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->service->delete($note);

        return response()->json(['message' => 'Note deleted successfully.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Note::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = $this->service->bulkDelete($request->ids);

        return response()->json([
            'message' => "{$deleted} Note(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Note::class);

        return $this->service->export($request);
    }
}
