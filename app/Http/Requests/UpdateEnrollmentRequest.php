<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->enrollment);
    }

    public function rules(): array
    {
        return [
            'classroom_id' => [
                'sometimes',
                'string',
                'exists:classrooms,public_id',
            ],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $startsAt = $this->input(
                'starts_at',
                $this->enrollment?->starts_at?->toDateString(),
            );
            $endsAt = array_key_exists('ends_at', $this->all())
                ? $this->input('ends_at')
                : $this->enrollment?->ends_at?->toDateString();

            if ($endsAt !== null && $startsAt !== null) {
                $start = strtotime((string) $startsAt);
                $end = strtotime((string) $endsAt);

                if ($start === false || $end === false || $end <= $start) {
                    $validator->errors()->add(
                        'ends_at',
                        'The ends_at field must be a date after starts_at.',
                    );
                }
            }
        });
    }
}
