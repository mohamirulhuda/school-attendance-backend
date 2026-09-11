<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Subject::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('subjects', 'code'),
            ],
            'name' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
