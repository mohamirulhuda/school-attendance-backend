<?php

namespace App\Http\Requests;

use App\Models\LearningGroupStudent;
use Illuminate\Foundation\Http\FormRequest;

class StoreLearningGroupStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LearningGroupStudent::class);
    }

    public function rules(): array
    {
        return [
            'learning_group_id' => [
                'required',
                'string',
                'exists:learning_groups,public_id',
            ],
            'student_id' => [
                'required',
                'string',
                'exists:students,public_id',
            ],
            'starts_at' => [
                'required',
                'date',
            ],
            'ends_at' => [
                'nullable',
                'date',
                'after:starts_at',
            ],
        ];
    }
}
