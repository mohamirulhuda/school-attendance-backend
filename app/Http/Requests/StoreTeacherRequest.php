<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Teacher::class);
    }

    public function rules(): array
    {
        return [
            'nip' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('teachers', 'nip'),
            ],
            'title_prefix' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string'],
            'nickname' => ['required', 'string', 'max:100'],
            'title_suffix' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', new Enum(Gender::class)],
            'email' => [
                'nullable',
                'email',
                Rule::unique('teachers', 'email'),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
