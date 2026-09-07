<?php

namespace App\Services\Attendance\Migration;

use App\Models\Teacher;

class LegacyTeacherMapper
{
    public function map(string $legacyTeacher): LegacyTeacherMappingResult
    {
        $identity = $this->parseLegacyTeacher($legacyTeacher);

        $teachers = Teacher::query()
            ->get()
            ->filter(
                fn (Teacher $teacher) =>
                    $this->canonicalTeacherIdentity(
                        $teacher->title_prefix,
                        $teacher->name,
                        $teacher->title_suffix,
                    ) === $identity
            );

        return match ($teachers->count()) {
            0 => new LegacyTeacherMappingResult(
                legacyName: $legacyTeacher,
                teacher: null,
                status: 'not_found',
            ),

            1 => new LegacyTeacherMappingResult(
                legacyName: $legacyTeacher,
                teacher: $teachers->first(),
                status: 'match',
            ),

            default => new LegacyTeacherMappingResult(
                legacyName: $legacyTeacher,
                teacher: null,
                status: 'ambiguous',
            ),
        };
    }

    private function parseLegacyTeacher(string $value): string
    {
        $value = trim($value);

        $parts = preg_split('/\s*,\s*/', $value);

        $firstPart = trim($parts[0] ?? '');

        $prefix = '';
        $name = $firstPart;

        if (preg_match('/^(Dr\.?|KH\.?)\s+(.+)$/i', $firstPart, $matches)) {
            $prefix = $matches[1];
            $name = $matches[2];
        }

        $suffix = implode(', ', array_slice($parts, 1));

        return $this->canonicalTeacherIdentity(
            $prefix,
            $name,
            $suffix,
        );
    }

    private function canonicalTeacherIdentity(
        ?string $prefix,
        ?string $name,
        ?string $suffix,
    ): string {
        return implode('|', [
            $this->normalizePrefix($prefix),
            $this->normalize($name),
            $this->normalize($suffix),
        ]);
    }

    private function normalizePrefix(?string $value): string
    {
        $value = $this->normalize($value);

        return rtrim($value, '.');
    }

    private function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = preg_replace('/\s*\.\s*/', '.', $value);

        return mb_strtolower($value);
    }
}
