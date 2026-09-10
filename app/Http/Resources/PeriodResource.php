<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['public_id' => $this->public_id, 'number' => $this->number, 'name' => $this->name, 'starts_at' => $this->starts_at?->format('H:i'), 'ends_at' => $this->ends_at?->format('H:i'), 'is_active' => $this->is_active];
    }
}
