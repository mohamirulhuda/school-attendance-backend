<?php

namespace App\Http\Requests;

use App\Models\LearningGroup;
use Illuminate\Foundation\Http\FormRequest;

class StoreLearningGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LearningGroup::class);
    }

    public function rules(): array
    {
        return [
            'academic_period_id' => [
                'required',
                'string',
                'exists:academic_periods,public_id',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
