<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Enrollment::class);
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'string',
                'exists:students,public_id',
            ],
            'classroom_id' => [
                'required',
                'string',
                'exists:classrooms,public_id',
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
