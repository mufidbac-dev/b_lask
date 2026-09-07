<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-task');
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required',
            'parent_task_id' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'required',
            'priority' => 'required',
            'due_at' => 'required|date',
            'timezone' => 'required',
            'completed_at' => 'required|date',
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
