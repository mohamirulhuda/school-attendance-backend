<?php

namespace App\Http\Requests;

use App\Models\Period;
use Illuminate\Foundation\Http\FormRequest;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Period::class);
    }

    public function rules(): array
    {
        return ['number' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:50'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'is_active' => ['sometimes', 'boolean']];
    }
}
