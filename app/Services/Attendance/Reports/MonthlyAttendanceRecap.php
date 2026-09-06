<?php

namespace App\Services\Attendance\Reports;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MonthlyAttendanceRecap
{
    public function execute(
        CarbonInterface $month,
        ?int $learningGroupId = null,
        ?int $teacherId = null,
        ?int $subjectId = null,
    ): Collection {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

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
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
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

        return $query
            ->selectRaw('
                students.id as student_id,
                students.name as student_name,
                SUM(CASE WHEN attendance_records.status = ? THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendance_records.status = ? THEN 1 ELSE 0 END) as sick,
                SUM(CASE WHEN attendance_records.status = ? THEN 1 ELSE 0 END) as excused,
                SUM(CASE WHEN attendance_records.status = ? THEN 1 ELSE 0 END) as absent,
                COUNT(attendance_records.id) as total
            ', [
                AttendanceStatus::Present->value,
                AttendanceStatus::Sick->value,
                AttendanceStatus::Excused->value,
                AttendanceStatus::Absent->value,
            ])
            ->groupBy('students.id', 'students.name')
            ->orderBy('students.name')
            ->get()
            ->map(fn ($row) => [
                'student_id' => (int) $row->student_id,
                'student_name' => $row->student_name,
                'present' => (int) $row->present,
                'sick' => (int) $row->sick,
                'excused' => (int) $row->excused,
                'absent' => (int) $row->absent,
                'total' => (int) $row->total,
            ]);
    }
}
