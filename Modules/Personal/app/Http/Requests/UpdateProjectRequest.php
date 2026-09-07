<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-project');
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:255',
            'status' => 'sometimes',
            'color' => 'sometimes',
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
