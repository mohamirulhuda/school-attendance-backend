<?php

namespace App\Http\Requests;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Classroom::class);
    }

    public function rules(): array
    {
        return [
            'grade' => ['required', 'integer', Rule::in([10, 11, 12])],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::in(['A', 'B', 'C', 'D', 'E', 'F', 'G']),
            ],
            'major' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
