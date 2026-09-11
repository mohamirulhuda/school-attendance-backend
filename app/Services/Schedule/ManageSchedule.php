<?php

namespace App\Services\Schedule;

use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageSchedule
{
    public function create(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {
            $dependencies = $this->resolveDependencies($data);

            $this->ensureDependenciesActive($dependencies);
            $this->ensureLearningGroupMatchesAcademicPeriod($dependencies);
            $this->resolveIds($data, $dependencies);
            $this->ensureNoSlotConflict($data);

            return Schedule::create($data);
        });
    }

    public function update(Schedule $schedule, array $data): Schedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $merged = array_merge(
                [
                    'academic_period_id' => $schedule->academicPeriod->public_id,
                    'learning_group_id' => $schedule->learningGroup->public_id,
                    'subject_id' => $schedule->subject->public_id,
                    'teacher_id' => $schedule->teacher->public_id,
                    'period_id' => $schedule->period->public_id,
                    'day_of_week' => $schedule->day_of_week->value,
                    'is_active' => $schedule->is_active,
                ],
                $data,
            );

            $dependencies = $this->resolveDependencies($merged);

            $this->ensureDependenciesActive($dependencies);
            $this->ensureLearningGroupMatchesAcademicPeriod($dependencies);
            $this->resolveIds($merged, $dependencies);

            $this->ensureNoSlotConflict($merged, $schedule);

            $schedule->update([
                'learning_group_id' => $merged['learning_group_id'],
                'subject_id' => $merged['subject_id'],
                'teacher_id' => $merged['teacher_id'],
                'period_id' => $merged['period_id'],
                'day_of_week' => $merged['day_of_week'],
                'is_active' => $merged['is_active'],
            ]);

            return $schedule->refresh();
        });
    }

    public function delete(Schedule $schedule): void
    {
        DB::transaction(fn () => $schedule->delete());
    }

    private function resolveDependencies(array $data): array
    {
        return [
            'academic_period' => AcademicPeriod::where(
                'public_id',
                $data['academic_period_id']
            )->firstOrFail(),
            'learning_group' => LearningGroup::where(
                'public_id',
                $data['learning_group_id']
            )->firstOrFail(),
            'subject' => Subject::where(
                'public_id',
                $data['subject_id']
            )->firstOrFail(),
            'teacher' => Teacher::where(
                'public_id',
                $data['teacher_id']
            )->firstOrFail(),
            'period' => Period::where(
                'public_id',
                $data['period_id']
            )->firstOrFail(),
        ];
    }

    private function ensureDependenciesActive(array $dependencies): void
    {
        foreach ($dependencies as $key => $model) {
            if (! $model->is_active) {
                $label = str_replace('_', ' ', ucfirst($key));

                throw ValidationException::withMessages([
                    $key.'_id' => $label.' must be active.',
                ]);
            }
        }
    }

    private function ensureLearningGroupMatchesAcademicPeriod(
        array $dependencies,
    ): void {
        if (
            $dependencies['learning_group']->academic_period_id
            !== $dependencies['academic_period']->id
        ) {
            throw ValidationException::withMessages([
                'learning_group_id' => 'Learning group must belong to the selected academic period.',
            ]);
        }
    }

    private function resolveIds(array &$data, array $dependencies): void
    {
        $data['academic_period_id'] = $dependencies['academic_period']->id;
        $data['learning_group_id'] = $dependencies['learning_group']->id;
        $data['subject_id'] = $dependencies['subject']->id;
        $data['teacher_id'] = $dependencies['teacher']->id;
        $data['period_id'] = $dependencies['period']->id;
    }

    private function ensureNoSlotConflict(
        array $data,
        ?Schedule $ignore = null,
    ): void {
        $query = Schedule::query()
            ->where(
                'academic_period_id',
                $data['academic_period_id']
            )
            ->where('is_active', true)
            ->where('day_of_week', $data['day_of_week'])
            ->where('period_id', $data['period_id']);

        $teacherQuery = (clone $query)
            ->where('teacher_id', $data['teacher_id']);

        $learningGroupQuery = (clone $query)
            ->where(
                'learning_group_id',
                $data['learning_group_id']
            );

        if ($ignore) {
            $teacherQuery->where('id', '<>', $ignore->id);
            $learningGroupQuery->where('id', '<>', $ignore->id);
        }

        if ($data['is_active'] && $teacherQuery->exists()) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Teacher already has a schedule in this period slot.',
            ]);
        }

        if ($data['is_active'] && $learningGroupQuery->exists()) {
            throw ValidationException::withMessages([
                'learning_group_id' => 'Learning group already has a schedule in this period slot.',
            ]);
        }
    }
}
