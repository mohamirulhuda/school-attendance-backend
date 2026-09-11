<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'academic_period' => [
                'public_id' => $this->academicPeriod->public_id,
                'academic_year' => $this->academicPeriod->academic_year,
                'semester' => $this->academicPeriod->semester->value,
            ],
        ];
    }
}
