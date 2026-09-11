<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningGroupStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'learning_group' => $this->whenLoaded(
                'learningGroup',
                fn () => [
                    'public_id' => $this->learningGroup->public_id,
                    'name' => $this->learningGroup->name,
                    'code' => $this->learningGroup->code,
                ],
            ),
            'student' => $this->whenLoaded(
                'student',
                fn () => [
                    'public_id' => $this->student->public_id,
                    'name' => $this->student->name,
                ],
            ),
        ];
    }
}
