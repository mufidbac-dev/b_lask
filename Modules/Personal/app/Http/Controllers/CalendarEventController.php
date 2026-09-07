<?php

namespace Modules\Personal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Personal\Http\Requests\StoreCalendarEventRequest;
use Modules\Personal\Http\Requests\UpdateCalendarEventRequest;
use Modules\Personal\Http\Resources\CalendarEventResource;
use Modules\Personal\Models\CalendarEvent;
use Modules\Personal\Services\CalendarEventService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CalendarEventController extends Controller
{
    public function __construct(
        private readonly CalendarEventService $service,
    ) {
        $this->authorizeResource(CalendarEvent::class, 'calendarEvent');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->service->paginate($request);

        return CalendarEventResource::collection($items);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return (new CalendarEventResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(CalendarEvent $calendarEvent): CalendarEventResource
    {
        $this->service->loadRelations($calendarEvent);

        return new CalendarEventResource($calendarEvent);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): CalendarEventResource
    {
        $item = $this->service->update($calendarEvent, $request->validated());

        return new CalendarEventResource($item);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $this->service->delete($calendarEvent);

        return response()->json(['message' => 'CalendarEvent deleted successfully.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', CalendarEvent::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = $this->service->bulkDelete($request->ids);

        return response()->json([
            'message' => "{$deleted} CalendarEvent(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', CalendarEvent::class);

        return $this->service->export($request);
    }
}
