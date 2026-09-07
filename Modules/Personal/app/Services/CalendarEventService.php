<?php

namespace Modules\Personal\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personal\Exports\CalendarEventExport;
use Modules\Personal\Models\CalendarEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CalendarEventService
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
        $query = CalendarEvent::query()->where('user_id', $request->user()->id);

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

    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::create([...$data, 'user_id' => auth()->id()]);
    }

    public function update(CalendarEvent $calendarEvent, array $data): CalendarEvent
    {
        $calendarEvent->update($data);

        return $calendarEvent->fresh();
    }

    public function delete(CalendarEvent $calendarEvent): bool
    {
        return (bool) $calendarEvent->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return CalendarEvent::where('user_id', auth()->id())->whereIn('id', $ids)->delete();
    }

    public function loadRelations(CalendarEvent $calendarEvent): void
    {
        if (! empty($this->relations)) {
            $calendarEvent->loadMissing($this->relations);
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new CalendarEventExport($request), 'calendar-events.xlsx');
    }
}
