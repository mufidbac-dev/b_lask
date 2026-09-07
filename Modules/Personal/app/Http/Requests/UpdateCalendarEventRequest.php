<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-calendar-event');
    }

    public function rules(): array
    {
        return [
            'project_id' => 'sometimes',
            'external_provider' => 'sometimes',
            'external_id' => 'sometimes',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:255',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
            'timezone' => 'sometimes',
            'status' => 'sometimes',
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
