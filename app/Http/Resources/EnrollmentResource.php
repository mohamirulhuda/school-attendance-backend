<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'academic_period' => $this->whenLoaded(
                'academicPeriod',
                fn () => [
                    'public_id' => $this->academicPeriod->public_id,
                    'academic_year' => $this->academicPeriod->academic_year,
                    'semester' => $this->academicPeriod->semester->value,
                ],
            ),
            'student' => $this->whenLoaded(
                'student',
                fn () => [
                    'public_id' => $this->student->public_id,
                    'name' => $this->student->name,
                ],
            ),
            'classroom' => $this->whenLoaded(
                'classroom',
                fn () => [
                    'public_id' => $this->classroom->public_id,
                    'name' => $this->classroom->name,
                ],
            ),
        ];
    }
}
