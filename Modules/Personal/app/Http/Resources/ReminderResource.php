<?php

namespace Modules\Personal\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'task_id' => $this->task_id,
            'note_id' => $this->note_id,
            'scheduled_at' => $this->scheduled_at,
            'timezone' => $this->timezone,
            'recurrence_rule' => $this->recurrence_rule,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user'),
            'task' => $this->whenLoaded('task'),
            'note' => $this->whenLoaded('note'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toISOString()),
        ];
    }
}
