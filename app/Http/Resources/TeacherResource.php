<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'nip' => $this->nip,
            'title_prefix' => $this->title_prefix,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'title_suffix' => $this->title_suffix,
            'gender' => $this->gender,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ];
    }
}
