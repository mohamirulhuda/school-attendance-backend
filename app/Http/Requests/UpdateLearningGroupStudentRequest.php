<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLearningGroupStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->learningGroupStudent);
    }

    public function rules(): array
    {
        return [
            'learning_group_id' => [
                'sometimes',
                'string',
                'exists:learning_groups,public_id',
            ],
            'student_id' => [
                'sometimes',
                'string',
                'exists:students,public_id',
            ],
            'starts_at' => [
                'sometimes',
                'date',
            ],
            'ends_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $start = $this->input('starts_at');
            $end = $this->input('ends_at');

            if ($start !== null && $end !== null && strtotime($end) <= strtotime($start)) {
                $validator->errors()->add(
                    'ends_at',
                    'The ends_at field must be a date after starts_at.',
                );
            }
        });
    }
}
