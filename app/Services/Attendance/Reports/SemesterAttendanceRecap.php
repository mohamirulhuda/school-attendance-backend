<?php

namespace App\Services\Attendance\Reports;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AcademicPeriod;
use App\Models\AttendanceSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SemesterAttendanceRecap
{
    public function execute(
        int $academicPeriodId,
        ?int $learningGroupId = null,
        ?int $teacherId = null,
        ?int $subjectId = null,
    ): Collection {
        $academicPeriod = AcademicPeriod::query()
            ->findOrFail($academicPeriodId);

        $rows = AttendanceSession::query()
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
                $academicPeriod->starts_at,
                $academicPeriod->ends_at,
            ])
            ->where(
                'attendance_sessions.status',
                AttendanceSessionStatus::Finalized->value,
            )
            ->when(
                $learningGroupId !== null,
                fn ($query) => $query->where(
                    'attendance_sessions.learning_group_id_snapshot',
                    $learningGroupId,
                ),
            )
            ->when(
                $teacherId !== null,
                fn ($query) => $query->where(
                    'attendance_sessions.teacher_id_snapshot',
                    $teacherId,
                ),
            )
            ->when(
                $subjectId !== null,
                fn ($query) => $query->where(
                    'attendance_sessions.subject_id_snapshot',
                    $subjectId,
                ),
            )
            ->select([
                'students.id as student_id',
                'students.name as student_name',
                'attendance_records.status as attendance_status',
            ])
            ->orderBy('students.name')
            ->get();

        return $rows
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

                $total = $present + $sick + $excused + $absent;

                return [
                    'student_id' => (int) $first->student_id,
                    'student_name' => $first->student_name,
                    'present' => $present,
                    'sick' => $sick,
                    'excused' => $excused,
                    'absent' => $absent,
                    'total' => $total,
                    'attendance_percentage' => $present > 0
                        ? round(($present / ($present + $sick + $excused + $absent)) * 100, 2)
                        : 0.0,                ];
            })
            ->values();
    }
}
