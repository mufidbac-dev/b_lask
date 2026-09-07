<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-calendar-event');
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required',
            'external_provider' => 'required',
            'external_id' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'timezone' => 'required',
            'status' => 'required',
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
