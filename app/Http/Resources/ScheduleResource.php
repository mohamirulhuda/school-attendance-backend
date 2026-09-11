<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'day_of_week' => $this->day_of_week?->value,
            'is_active' => $this->is_active,
            'academic_period' => $this->whenLoaded(
                'academicPeriod',
                fn () => [
                    'public_id' => $this->academicPeriod->public_id,
                    'academic_year' => $this->academicPeriod->academic_year,
                    'semester' => $this->academicPeriod->semester->value,
                ]
            ),
            'learning_group' => $this->whenLoaded(
                'learningGroup',
                fn () => [
                    'public_id' => $this->learningGroup->public_id,
                    'name' => $this->learningGroup->name,
                    'code' => $this->learningGroup->code,
                ]
            ),
            'subject' => $this->whenLoaded(
                'subject',
                fn () => [
                    'public_id' => $this->subject->public_id,
                    'code' => $this->subject->code,
                    'name' => $this->subject->name,
                ]
            ),
            'teacher' => $this->whenLoaded(
                'teacher',
                fn () => [
                    'public_id' => $this->teacher->public_id,
                    'name' => $this->teacher->name,
                    'nickname' => $this->teacher->nickname,
                ]
            ),
            'period' => $this->whenLoaded(
                'period',
                fn () => [
                    'public_id' => $this->period->public_id,
                    'number' => $this->period->number,
                    'name' => $this->period->name,
                    'starts_at' => $this->period->starts_at?->format('H:i'),
                    'ends_at' => $this->period->ends_at?->format('H:i'),
                ]
            ),
        ];
    }
}
