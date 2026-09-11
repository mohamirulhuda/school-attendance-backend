<?php

namespace App\Services\LearningGroupStudent;

use App\Models\LearningGroup;
use App\Models\LearningGroupStudent;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageLearningGroupStudent
{
    public function create(array $data): LearningGroupStudent
    {
        return DB::transaction(function () use ($data) {
            $learningGroup = LearningGroup::query()
                ->where('public_id', $data['learning_group_id'])
                ->firstOrFail();

            $student = Student::query()
                ->where('public_id', $data['student_id'])
                ->firstOrFail();

            $this->resolveIds($data, $learningGroup, $student);

            $this->ensureNoOverlap(
                $student->id,
                $learningGroup->id,
                $data['starts_at'],
                $data['ends_at'] ?? null,
            );

            return LearningGroupStudent::create($data);
        });
    }

    public function update(
        LearningGroupStudent $membership,
        array $data,
    ): LearningGroupStudent {
        return DB::transaction(function () use ($membership, $data) {
            $learningGroupPublicId = $data['learning_group_id']
                ?? $membership->learningGroup->public_id;
            $studentPublicId = $data['student_id']
                ?? $membership->student->public_id;

            $learningGroup = LearningGroup::query()
                ->where('public_id', $learningGroupPublicId)
                ->firstOrFail();

            $student = Student::query()
                ->where('public_id', $studentPublicId)
                ->firstOrFail();

            $start = $data['starts_at']
                ?? $membership->starts_at->toDateString();
            $end = array_key_exists('ends_at', $data)
                ? $data['ends_at']
                : $membership->ends_at?->toDateString();

            $this->ensureNoOverlap(
                $student->id,
                $learningGroup->id,
                $start,
                $end,
                $membership,
            );

            $data['learning_group_id'] = $learningGroup->id;
            $data['student_id'] = $student->id;

            $membership->update($data);

            return $membership->refresh();
        });
    }

    public function delete(LearningGroupStudent $membership): void
    {
        DB::transaction(fn () => $membership->delete());
    }

    private function ensureNoOverlap(
        int $studentId,
        int $learningGroupId,
        string $start,
        ?string $end,
        ?LearningGroupStudent $ignore = null,
    ): void {
        $newStart = Carbon::parse($start);
        $newEnd = $end ? Carbon::parse($end) : null;

        if ($newEnd !== null && $newEnd->lte($newStart)) {
            throw ValidationException::withMessages([
                'ends_at' => 'The ends_at field must be a date after starts_at.',
            ]);
        }

        $query = LearningGroupStudent::query()
            ->where('student_id', $studentId)
            ->where('learning_group_id', $learningGroupId);

        if ($ignore !== null) {
            $query->where('id', '<>', $ignore->getKey());
        }

        foreach ($query->get() as $existing) {
            $existingStart = Carbon::parse($existing->starts_at);
            $existingEnd = $existing->ends_at
                ? Carbon::parse($existing->ends_at)
                : null;

            $overlaps = $newStart < ($existingEnd ?? Carbon::create(9999, 12, 31))
                && $existingStart < ($newEnd ?? Carbon::create(9999, 12, 31));

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Membership period overlaps an existing membership.',
                ]);
            }
        }
    }

    private function resolveIds(
        array &$data,
        LearningGroup $learningGroup,
        Student $student,
    ): void {
        $data['learning_group_id'] = $learningGroup->id;
        $data['student_id'] = $student->id;
    }
}
