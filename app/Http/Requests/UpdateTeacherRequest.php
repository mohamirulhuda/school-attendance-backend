<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->teacher);
    }

    public function rules(): array
    {
        return [
            'nip' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('teachers', 'nip')->ignore($this->teacher?->id),
            ],
            'title_prefix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'name' => ['sometimes', 'string'],
            'nickname' => ['sometimes', 'required', 'string', 'max:100'],
            'title_suffix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'gender' => ['sometimes', new Enum(Gender::class)],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('teachers', 'email')->ignore($this->teacher?->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
