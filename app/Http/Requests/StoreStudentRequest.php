<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }

    public function rules(): array
    {
        return [
            'nis' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('students', 'nis'),
            ],
            'nisn' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students', 'nisn'),
            ],
            'name' => ['required', 'string'],
            'gender' => ['required', new Enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
