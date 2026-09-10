<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->period);
    }

    public function rules(): array
    {
        return ['number' => ['sometimes', 'integer', 'min:1'], 'name' => ['sometimes', 'string', 'max:50'], 'starts_at' => ['sometimes', 'date_format:H:i'], 'ends_at' => ['sometimes', 'date_format:H:i'], 'is_active' => ['sometimes', 'boolean']];
    }
}
