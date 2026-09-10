<?php

namespace App\Services\Period;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagePeriod
{
    public function create(array $data): Period
    {
        return DB::transaction(function () use ($data) {
            $this->validateTimeRange($data);

            $period = Period::create($data);

            $this->validateActiveConfiguration();

            return $period->refresh();
        });
    }

    public function update(Period $period, array $data): Period
    {
        return DB::transaction(function () use ($period, $data) {
            $merged = array_merge(
                $period->only([
                    'number',
                    'name',
                    'starts_at',
                    'ends_at',
                    'is_active',
                ]),
                $data
            );

            $this->validateTimeRange($merged);

            $period->update($data);

            $this->validateActiveConfiguration();

            return $period->refresh();
        });
    }

    private function validateTimeRange(array $data): void
    {
        $startsAt = $this->normalizeTime($data['starts_at']);
        $endsAt = $this->normalizeTime($data['ends_at']);

        if ($startsAt >= $endsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'Period start time must be earlier than end time.',
            ]);
        }
    }

    private function validateActiveConfiguration(): void
    {
        $periods = Period::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get([
                'id',
                'number',
                'starts_at',
                'ends_at',
            ]);

        $numbers = $periods
            ->pluck('number')
            ->map(fn ($number) => (int) $number)
            ->values()
            ->all();

        if ($numbers !== range(1, count($numbers))) {
            throw ValidationException::withMessages([
                'number' => 'Active Period numbers must start at 1 and have no gaps.',
            ]);
        }

        $periods = $periods
            ->sortBy(fn ($period) => $this->normalizeTime($period->starts_at))
            ->values();

        for ($i = 1; $i < $periods->count(); $i++) {
            $previous = $periods[$i - 1];
            $current = $periods[$i];

            if (
                $this->normalizeTime($current->starts_at)
                < $this->normalizeTime($previous->ends_at)
            ) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Active Period time ranges must not overlap.',
                ]);
            }
        }
    }

    private function normalizeTime(mixed $value): string
    {
        return $value instanceof Carbon
            ? $value->format('H:i')
            : Carbon::createFromFormat('H:i', (string) $value)->format('H:i');
    }
}
