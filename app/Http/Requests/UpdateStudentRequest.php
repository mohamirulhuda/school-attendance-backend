<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->student);
    }

    public function rules(): array
    {
        return [
            'nis' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('students', 'nis')->ignore($this->student?->id),
            ],
            'nisn' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('students', 'nisn')->ignore($this->student?->id),
            ],
            'name' => ['sometimes', 'string'],
            'gender' => ['sometimes', new Enum(Gender::class)],
            'birth_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
