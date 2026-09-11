<?php

namespace App\Http\Requests;

use App\Enums\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->schedule);
    }

    public function rules(): array
    {
        return [
            'learning_group_id' => [
                'sometimes',
                'string',
                'exists:learning_groups,public_id',
            ],
            'subject_id' => [
                'sometimes',
                'string',
                'exists:subjects,public_id',
            ],
            'teacher_id' => [
                'sometimes',
                'string',
                'exists:teachers,public_id',
            ],
            'period_id' => [
                'sometimes',
                'string',
                'exists:periods,public_id',
            ],
            'day_of_week' => [
                'sometimes',
                new Enum(DayOfWeek::class),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
