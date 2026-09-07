<?php

namespace Modules\Personal\Exports;

class ReminderExport
{
    public function headings(): array
    {
        return [
            'User Id',
            'Task Id',
            'Note Id',
            'Scheduled At',
            'Timezone',
            'Recurrence Rule',
            'Status',
            'Last Error',
            'Sent At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $row->task_id,
            $row->note_id,
            $row->scheduled_at,
            $row->timezone,
            $row->recurrence_rule,
            $row->status,
            $row->last_error,
            $row->sent_at,
        ];
    }
}
