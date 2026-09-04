<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Schedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartAttendanceSession
{
    public function execute(
        int $scheduleId,
        CarbonInterface $attendanceDate,
    ): AttendanceSession {
        return DB::transaction(function () use ($scheduleId, $attendanceDate) {
            $schedule = Schedule::query()
                ->with('academicPeriod')
                ->lockForUpdate()
                ->findOrFail($scheduleId);

            $existingSession = AttendanceSession::query()
                ->where('schedule_id', $schedule->id)
                ->whereDate('date', $attendanceDate)
                ->lockForUpdate()
                ->first();

            if ($existingSession) {
                return $existingSession;
            }

            $academicPeriod = $schedule->academicPeriod;

            if (
                $attendanceDate->lt($academicPeriod->starts_at) ||
                $attendanceDate->gt($academicPeriod->ends_at)
            ) {
                throw ValidationException::withMessages([
                    'attendance_date' => 'Attendance date is outside the academic period.',
                ]);
            }

            $eligibleMemberships = $schedule->learningGroup
                ->memberships()
                ->with('student')
                ->whereDate('starts_at', '<=', $attendanceDate)
                ->where(function ($query) use ($attendanceDate) {
                    $query
                        ->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', $attendanceDate);
                })
                ->get();

            if ($eligibleMemberships->isEmpty()) {
                throw ValidationException::withMessages([
                    'attendance_date' => 'No eligible students found for this attendance date.',
                ]);
            }

            $session = AttendanceSession::query()->create([
                'schedule_id' => $schedule->id,
                'teacher_id_snapshot' => $schedule->teacher_id,
                'subject_id_snapshot' => $schedule->subject_id,
                'learning_group_id_snapshot' => $schedule->learning_group_id,
                'period_id_snapshot' => $schedule->period_id,
                'date' => $attendanceDate,
                'status' => AttendanceSessionStatus::Draft,
                'opened_at' => now(),
            ]);

            $session->attendanceRecords()->createMany(
                $eligibleMemberships->map(
                    fn ($membership) => [
                        'student_id' => $membership->student_id,
                        'status' => AttendanceStatus::Present,
                    ]
                )->all()
            );

            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'domain' => 'attendance',
                'action' => 'create',
                'auditable_type' => AttendanceSession::class,
                'auditable_id' => $session->id,
                'old_values' => null,
                'new_values' => [
                    'schedule_id' => $session->schedule_id,
                    'teacher_id_snapshot' => $session->teacher_id_snapshot,
                    'subject_id_snapshot' => $session->subject_id_snapshot,
                    'learning_group_id_snapshot' => $session->learning_group_id_snapshot,
                    'period_id_snapshot' => $session->period_id_snapshot,
                    'date' => $session->date->toDateString(),
                    'status' => $session->status->value,
                ],
            ]);

            return $session->load('attendanceRecords');
        });
    }
}
