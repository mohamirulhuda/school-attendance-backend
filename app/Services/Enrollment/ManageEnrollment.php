<?php

namespace App\Services\Enrollment;

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageEnrollment
{
    public function create(array $data): Enrollment
    {
        return DB::transaction(function () use ($data) {
            $academicPeriod = AcademicPeriod::query()
                ->where('is_active', true)
                ->orderByDesc('starts_at')
                ->first();

            if (! $academicPeriod) {
                throw ValidationException::withMessages([
                    'academic_period' => 'No active academic period is configured.',
                ]);
            }

            $student = Student::query()
                ->where('public_id', $data['student_id'])
                ->firstOrFail();

            $classroom = Classroom::query()
                ->where('public_id', $data['classroom_id'])
                ->firstOrFail();

            if (! $student->is_active) {
                throw ValidationException::withMessages([
                    'student_id' => 'Student must be active.',
                ]);
            }

            if (! $classroom->is_active) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'Classroom must be active.',
                ]);
            }

            $data['academic_period_id'] = $academicPeriod->id;
            $data['student_id'] = $student->id;
            $data['classroom_id'] = $classroom->id;

            $this->ensureNoOverlap(
                $student->id,
                $academicPeriod->id,
                $data['starts_at'],
                $data['ends_at'] ?? null,
            );

            return Enrollment::create($data);
        });
    }

    public function update(
        Enrollment $enrollment,
        array $data,
    ): Enrollment {
        return DB::transaction(function () use ($enrollment, $data) {
            if (isset($data['classroom_id'])) {
                $classroom = Classroom::query()
                    ->where('public_id', $data['classroom_id'])
                    ->firstOrFail();

                if (! $classroom->is_active) {
                    throw ValidationException::withMessages([
                        'classroom_id' => 'Classroom must be active.',
                    ]);
                }

                $data['classroom_id'] = $classroom->id;
            }

            $startsAt = $data['starts_at']
                ?? $enrollment->starts_at->toDateString();

            $endsAt = array_key_exists('ends_at', $data)
                ? $data['ends_at']
                : $enrollment->ends_at?->toDateString();

            $this->ensureNoOverlap(
                $enrollment->student_id,
                $enrollment->academic_period_id,
                $startsAt,
                $endsAt,
                $enrollment,
            );

            $enrollment->update($data);

            return $enrollment->refresh();
        });
    }

    private function ensureNoOverlap(
        int $studentId,
        int $academicPeriodId,
        string $startsAt,
        ?string $endsAt,
        ?Enrollment $ignore = null,
    ): void {
        $query = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('academic_period_id', $academicPeriodId);

        if ($ignore !== null) {
            $query->where('id', '<>', $ignore->id);
        }

        $newStart = Carbon::parse($startsAt);
        $newEnd = $endsAt !== null
            ? Carbon::parse($endsAt)
            : null;

        if ($newEnd !== null && $newEnd->lte($newStart)) {
            throw ValidationException::withMessages([
                'ends_at' => 'The ends_at field must be after starts_at.',
            ]);
        }

        foreach ($query->get() as $existing) {
            $existingStart = Carbon::parse($existing->starts_at);
            $existingEnd = $existing->ends_at !== null
                ? Carbon::parse($existing->ends_at)
                : null;

            $overlaps = $newStart < (
                $existingEnd ?? Carbon::create(9999, 12, 31)
            ) && $existingStart < (
                $newEnd ?? Carbon::create(9999, 12, 31)
            );

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'starts_at' => ['Enrollment period overlaps an existing enrollment.'],
                    'ends_at' => ['Enrollment period overlaps an existing enrollment.'],
                ]);
            }
        }
    }
}
