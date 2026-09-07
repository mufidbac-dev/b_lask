<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-note');
    }

    public function rules(): array
    {
        return [
            'project_id' => 'sometimes',
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes',
            'visibility' => 'sometimes',
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
