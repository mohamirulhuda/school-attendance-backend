<?php

namespace App\Services\Attendance\Reports;

use App\Models\Schedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceCompletionReport
{
    public function execute(
        CarbonInterface $date,
        ?int $learningGroupId = null,
        ?int $teacherId = null,
        ?int $subjectId = null,
    ): Collection {
        $dayOfWeek = $date->dayOfWeekIso;

        $schedules = Schedule::query()
            ->with([
                'period:id,number,name,starts_at,ends_at',
            ])
            ->withExists([
                'attendanceSessions as attendance_session_exists' => function ($query) use ($date) {
                    $query->whereDate('date', $date->toDateString());
                },
            ])
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('academicPeriod', function ($query) use ($date) {
                $query
                    ->whereDate('starts_at', '<=', $date->toDateString())
                    ->whereDate('ends_at', '>=', $date->toDateString());
            })
            ->when(
                $learningGroupId !== null,
                fn ($query) => $query->where(
                    'learning_group_id',
                    $learningGroupId,
                ),
            )
            ->when(
                $teacherId !== null,
                fn ($query) => $query->where(
                    'teacher_id',
                    $teacherId,
                ),
            )
            ->when(
                $subjectId !== null,
                fn ($query) => $query->where(
                    'subject_id',
                    $subjectId,
                ),
            )
            ->orderBy('period_id')
            ->get();

        return $schedules->map(function (Schedule $schedule) use ($date) {
            return [
                'schedule_id' => $schedule->id,
                'date' => $date->toDateString(),
                'teacher_id' => $schedule->teacher_id,
                'subject_id' => $schedule->subject_id,
                'learning_group_id' => $schedule->learning_group_id,
                'period_id' => $schedule->period_id,
                'period' => [
                    'number' => $schedule->period->number,
                    'name' => $schedule->period->name,
                    'starts_at' => $schedule->period->starts_at,
                    'ends_at' => $schedule->period->ends_at,
                ],
                'status' => $schedule->attendance_session_exists
                    ? 'filled'
                    : 'unfilled',
            ];
        });
    }
}
