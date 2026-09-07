<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-reminder');
    }

    public function rules(): array
    {
        return [
            'task_id' => 'sometimes',
            'note_id' => 'sometimes',
            'scheduled_at' => 'sometimes|date',
            'timezone' => 'sometimes',
            'recurrence_rule' => 'sometimes',
            'status' => 'sometimes',
            'last_error' => 'sometimes',
            'sent_at' => 'sometimes|date',
        ];
    }

    public function attributes(): array
    {
        return [
            // 'field_name' => 'Human Readable Name',
        ];
    }

    public function messages(): array
    {
        return [
            // 'field_name.required' => 'Custom message.',
        ];
    }
}
