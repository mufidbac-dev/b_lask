<?php

namespace Modules\Personal\Exports;

class ProjectExport
{
    public function headings(): array
    {
        return [
            'User Id',
            'Name',
            'Description',
            'Status',
            'Color',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $row->name,
            $row->description,
            $row->status,
            $row->color,
        ];
    }
}
