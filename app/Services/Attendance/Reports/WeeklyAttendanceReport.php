<?php

namespace App\Services\Attendance\Reports;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class WeeklyAttendanceReport
{
    public function execute(
        CarbonInterface $month,
        ?int $learningGroupId = null,
        ?int $teacherId = null,
        ?int $subjectId = null,
    ): Collection {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $query = AttendanceSession::query()
            ->join(
                'attendance_records',
                'attendance_records.attendance_session_id',
                '=',
                'attendance_sessions.id',
            )
            ->join(
                'students',
                'students.id',
                '=',
                'attendance_records.student_id',
            )
            ->whereBetween('attendance_sessions.date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->where(
                'attendance_sessions.status',
                AttendanceSessionStatus::Finalized->value,
            );

        if ($learningGroupId !== null) {
            $query->where(
                'attendance_sessions.learning_group_id_snapshot',
                $learningGroupId,
            );
        }

        if ($teacherId !== null) {
            $query->where(
                'attendance_sessions.teacher_id_snapshot',
                $teacherId,
            );
        }

        if ($subjectId !== null) {
            $query->where(
                'attendance_sessions.subject_id_snapshot',
                $subjectId,
            );
        }

        $rows = $query
            ->select([
                'attendance_sessions.date',
                'students.id as student_id',
                'students.name as student_name',
                'attendance_records.status as attendance_status',
            ])
            ->orderBy('attendance_sessions.date')
            ->orderBy('students.name')
            ->get();

        $weeks = collect();

        $currentWeekStart = $monthStart->copy()->startOfWeek(Carbon::SATURDAY);

        while ($currentWeekStart->lte($monthEnd)) {
            $weekStart = $currentWeekStart->copy();
            $weekEnd = $weekStart->copy()->addDays(5);

            $weekRows = $rows->filter(function ($row) use (
                $weekStart,
                $weekEnd,
            ) {
                $date = $row->date;

                return $date >= $weekStart->toDateString()
                    && $date <= $weekEnd->toDateString();
            });

            $students = $weekRows
                ->groupBy('student_id')
                ->map(function (Collection $studentRows) {
                    $first = $studentRows->first();

                    $present = $studentRows->where(
                        'attendance_status',
                        AttendanceStatus::Present->value,
                    )->count();

                    $sick = $studentRows->where(
                        'attendance_status',
                        AttendanceStatus::Sick->value,
                    )->count();

                    $excused = $studentRows->where(
                        'attendance_status',
                        AttendanceStatus::Excused->value,
                    )->count();

                    $absent = $studentRows->where(
                        'attendance_status',
                        AttendanceStatus::Absent->value,
                    )->count();

                    return [
                        'student_id' => (int) $first->student_id,
                        'student_name' => $first->student_name,
                        'present' => $present,
                        'sick' => $sick,
                        'excused' => $excused,
                        'absent' => $absent,
                        'total' => $present + $sick + $excused + $absent,
                    ];
                })
                ->values()
                ->all();

            $weeks->push([
                'week' => $weeks->count() + 1,
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
                'students' => $students,
            ]);

            $currentWeekStart->addDays(7);
        }

        return $weeks;
    }
}
