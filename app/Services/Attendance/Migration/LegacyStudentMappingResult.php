<?php

namespace App\Services\Attendance\Migration;

use App\Models\Student;

class LegacyStudentMappingResult
{
    public function __construct(
        public readonly string $legacyName,
        public readonly ?Student $student,
        public readonly string $status,
    ) {
    }

    public function isMatched(): bool
    {
        return $this->status === 'match';
    }
}
