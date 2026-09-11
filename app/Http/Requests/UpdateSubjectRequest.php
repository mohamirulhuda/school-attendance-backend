<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->subject);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('subjects', 'code')->ignore($this->subject?->id),
            ],
            'name' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
