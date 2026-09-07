<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-task');
    }

    public function rules(): array
    {
        return [
            'project_id' => 'sometimes',
            'parent_task_id' => 'sometimes',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:255',
            'status' => 'sometimes',
            'priority' => 'sometimes',
            'due_at' => 'sometimes|date',
            'timezone' => 'sometimes',
            'completed_at' => 'sometimes|date',
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
