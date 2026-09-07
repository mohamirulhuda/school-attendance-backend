<?php

namespace App\Services\Attendance\Migration;

use App\Models\Student;

class LegacyStudentMapper
{
    public function map(string $legacyStudent): LegacyStudentMappingResult
    {
        $identity = $this->normalize($legacyStudent);

        $students = Student::query()
            ->get()
            ->filter(
                fn (Student $student) =>
                    $this->normalize($student->name) === $identity
            );

        return match ($students->count()) {
            0 => new LegacyStudentMappingResult(
                legacyName: $legacyStudent,
                student: null,
                status: 'not_found',
            ),

            1 => new LegacyStudentMappingResult(
                legacyName: $legacyStudent,
                student: $students->first(),
                status: 'match',
            ),

            default => new LegacyStudentMappingResult(
                legacyName: $legacyStudent,
                student: null,
                status: 'ambiguous',
            ),
        };
    }

    private function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return mb_strtolower($value);
    }
}
