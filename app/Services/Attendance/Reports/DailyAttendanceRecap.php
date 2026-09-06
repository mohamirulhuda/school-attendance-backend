<?php

namespace App\Services\Attendance\Reports;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyAttendanceRecap
{
    public function execute(
        CarbonInterface $date,
        ?int $learningGroupId = null,
        ?int $teacherId = null,
        ?int $subjectId = null,
    ): Collection {
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
            ->join(
                'periods',
                'periods.id',
                '=',
                'attendance_sessions.period_id_snapshot',
            )
            ->whereDate('attendance_sessions.date', $date->toDateString())
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
                'attendance_sessions.id as session_id',
                'attendance_sessions.date',
                'attendance_sessions.teacher_id_snapshot as teacher_id',
                'attendance_sessions.subject_id_snapshot as subject_id',
                'attendance_sessions.learning_group_id_snapshot as learning_group_id',
                'attendance_sessions.period_id_snapshot as period_id',
                'periods.number as period_number',
                'periods.name as period_name',
                'periods.starts_at as period_starts_at',
                'periods.ends_at as period_ends_at',
                'students.id as student_id',
                'students.name as student_name',
                'attendance_records.status as attendance_status',
                'attendance_records.note',
            ])
            ->orderBy('periods.number')
            ->orderBy('students.name')
            ->get();

        return $rows
            ->groupBy('session_id')
            ->map(function (Collection $sessionRows) {
                $first = $sessionRows->first();

                return [
                    'session_id' => (int) $first->session_id,
                    'date' => $first->date->toDateString(),
                    'teacher_id' => (int) $first->teacher_id,
                    'subject_id' => (int) $first->subject_id,
                    'learning_group_id' => (int) $first->learning_group_id,
                    'period_id' => (int) $first->period_id,

                    'period' => [
                        'number' => (int) $first->period_number,
                        'name' => $first->period_name,
                        'starts_at' => $first->period_starts_at,
                        'ends_at' => $first->period_ends_at,
                    ],

                    'students' => $sessionRows
                        ->map(fn ($row) => [
                            'student_id' => (int) $row->student_id,
                            'student_name' => $row->student_name,
                            'status' => match ($row->attendance_status) {
                                AttendanceStatus::Present->value => 'present',
                                AttendanceStatus::Sick->value => 'sick',
                                AttendanceStatus::Excused->value => 'excused',
                                AttendanceStatus::Absent->value => 'absent',
                            },
                            'note' => $row->note,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();
    }
}
