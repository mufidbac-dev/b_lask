<?php

namespace Modules\Personal\Exports;

class TagExport
{
    public function headings(): array
    {
        return [
            'User Id',
            'Name',
            'Color',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $row->name,
            $row->color,
        ];
    }
}
