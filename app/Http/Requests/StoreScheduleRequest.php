<?php

namespace App\Http\Requests;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Schedule::class);
    }

    public function rules(): array
    {
        return [
            'academic_period_id' => [
                'required',
                'string',
                'exists:academic_periods,public_id',
            ],
            'learning_group_id' => [
                'required',
                'string',
                'exists:learning_groups,public_id',
            ],
            'subject_id' => [
                'required',
                'string',
                'exists:subjects,public_id',
            ],
            'teacher_id' => [
                'required',
                'string',
                'exists:teachers,public_id',
            ],
            'period_id' => [
                'required',
                'string',
                'exists:periods,public_id',
            ],
            'day_of_week' => [
                'required',
                new Enum(DayOfWeek::class),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
