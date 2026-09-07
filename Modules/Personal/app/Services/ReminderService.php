<?php

namespace Modules\Personal\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personal\Exports\ReminderExport;
use Modules\Personal\Models\Reminder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReminderService
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
        $query = Reminder::query()->where('user_id', $request->user()->id);

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

    public function create(array $data): Reminder
    {
        return Reminder::create([...$data, 'user_id' => auth()->id()]);
    }

    public function update(Reminder $reminder, array $data): Reminder
    {
        $reminder->update($data);

        return $reminder->fresh();
    }

    public function delete(Reminder $reminder): bool
    {
        return (bool) $reminder->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Reminder::where('user_id', auth()->id())->whereIn('id', $ids)->delete();
    }

    public function loadRelations(Reminder $reminder): void
    {
        if (! empty($this->relations)) {
            $reminder->loadMissing($this->relations);
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new ReminderExport($request), 'reminders.xlsx');
    }
}
