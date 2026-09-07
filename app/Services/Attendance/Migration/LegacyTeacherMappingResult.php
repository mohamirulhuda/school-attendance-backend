<?php

namespace App\Services\Attendance\Migration;

use App\Models\Teacher;

class LegacyTeacherMappingResult
{
    public function __construct(
        public readonly string $legacyName,
        public readonly ?Teacher $teacher,
        public readonly string $status,
    ) {
    }

    public function isMatched(): bool
    {
        return $this->status === 'match';
    }
}
