<?php

namespace Modules\Personal\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personal\Exports\NoteExport;
use Modules\Personal\Models\Note;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NoteService
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
        $query = Note::query()->where('user_id', $request->user()->id);

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

    public function create(array $data): Note
    {
        return Note::create([...$data, 'user_id' => auth()->id()]);
    }

    public function update(Note $note, array $data): Note
    {
        $note->update($data);

        return $note->fresh();
    }

    public function delete(Note $note): bool
    {
        return (bool) $note->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Note::where('user_id', auth()->id())->whereIn('id', $ids)->delete();
    }

    public function loadRelations(Note $note): void
    {
        if (! empty($this->relations)) {
            $note->loadMissing($this->relations);
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new NoteExport($request), 'notes.xlsx');
    }
}
