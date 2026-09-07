<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-reminder');
    }

    public function rules(): array
    {
        return [
            'task_id' => 'required',
            'note_id' => 'required',
            'scheduled_at' => 'required|date',
            'timezone' => 'required',
            'recurrence_rule' => 'required',
            'status' => 'required',
            'last_error' => 'required',
            'sent_at' => 'required|date',
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
