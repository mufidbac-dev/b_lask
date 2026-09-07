<?php

namespace Modules\Personal\Exports;

class CalendarEventExport
{
    public function headings(): array
    {
        return [
            'User Id',
            'Project Id',
            'External Provider',
            'External Id',
            'Title',
            'Description',
            'Starts At',
            'Ends At',
            'Timezone',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $row->project_id,
            $row->external_provider,
            $row->external_id,
            $row->title,
            $row->description,
            $row->starts_at,
            $row->ends_at,
            $row->timezone,
            $row->status,
        ];
    }
}
