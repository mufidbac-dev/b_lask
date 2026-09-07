<?php

namespace Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-tag');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'required',
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
