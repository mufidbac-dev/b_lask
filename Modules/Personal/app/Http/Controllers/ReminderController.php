<?php

namespace Modules\Personal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Personal\Http\Requests\StoreReminderRequest;
use Modules\Personal\Http\Requests\UpdateReminderRequest;
use Modules\Personal\Http\Resources\ReminderResource;
use Modules\Personal\Models\Reminder;
use Modules\Personal\Services\ReminderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReminderController extends Controller
{
    public function __construct(
        private readonly ReminderService $service,
    ) {
        $this->authorizeResource(Reminder::class, 'reminder');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->service->paginate($request);

        return ReminderResource::collection($items);
    }

    public function store(StoreReminderRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return (new ReminderResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Reminder $reminder): ReminderResource
    {
        $this->service->loadRelations($reminder);

        return new ReminderResource($reminder);
    }

    public function update(UpdateReminderRequest $request, Reminder $reminder): ReminderResource
    {
        $item = $this->service->update($reminder, $request->validated());

        return new ReminderResource($item);
    }

    public function destroy(Reminder $reminder): JsonResponse
    {
        $this->service->delete($reminder);

        return response()->json(['message' => 'Reminder deleted successfully.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Reminder::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = $this->service->bulkDelete($request->ids);

        return response()->json([
            'message' => "{$deleted} Reminder(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Reminder::class);

        return $this->service->export($request);
    }
}
