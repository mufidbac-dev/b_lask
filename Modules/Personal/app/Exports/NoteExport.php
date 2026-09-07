<?php

namespace Modules\Personal\Exports;

class NoteExport
{
    public function headings(): array
    {
        return [
            'User Id',
            'Project Id',
            'Title',
            'Content',
            'Visibility',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $row->project_id,
            $row->title,
            $row->content,
            $row->visibility,
        ];
    }
}
