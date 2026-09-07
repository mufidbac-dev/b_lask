<?php

namespace Modules\Personal\Exports;

class TaskExport
{
    public function headings(): array
    {
        return [
            'User Id',
            'Project Id',
            'Parent Task Id',
            'Title',
            'Description',
            'Status',
            'Priority',
            'Due At',
            'Timezone',
            'Completed At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $row->project_id,
            $row->parent_task_id,
            $row->title,
            $row->description,
            $row->status,
            $row->priority,
            $row->due_at,
            $row->timezone,
            $row->completed_at,
        ];
    }
}
