<?php

namespace App\Services\Attendance\Migration;

use App\Models\Schedule;

class LegacyScheduleMappingResult
{
    public function __construct(
        public readonly ?Schedule $schedule,
        public readonly string $status,
    ) {
    }

    public function isMatched(): bool
    {
        return $this->status === 'match';
    }
}
