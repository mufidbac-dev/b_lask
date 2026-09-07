<?php

namespace Modules\Personal\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personal\Exports\TagExport;
use Modules\Personal\Models\Tag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TagService
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
        $query = Tag::query()->where('user_id', $request->user()->id);

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

    public function create(array $data): Tag
    {
        return Tag::create([...$data, 'user_id' => auth()->id()]);
    }

    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);

        return $tag->fresh();
    }

    public function delete(Tag $tag): bool
    {
        return (bool) $tag->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Tag::where('user_id', auth()->id())->whereIn('id', $ids)->delete();
    }

    public function loadRelations(Tag $tag): void
    {
        if (! empty($this->relations)) {
            $tag->loadMissing($this->relations);
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new TagExport($request), 'tags.xlsx');
    }
}
