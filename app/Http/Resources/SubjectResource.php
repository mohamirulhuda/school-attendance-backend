<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
