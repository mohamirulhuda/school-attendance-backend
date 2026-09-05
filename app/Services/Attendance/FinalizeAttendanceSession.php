<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceSessionStatus;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\LearningGroupStudent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinalizeAttendanceSession
{
    public function execute(int $attendanceSessionId): AttendanceSession
    {
        return DB::transaction(function () use ($attendanceSessionId) {
            $session = AttendanceSession::query()
                ->lockForUpdate()
                ->findOrFail($attendanceSessionId);

            Gate::authorize('finalize', $session);

            if ($session->status !== AttendanceSessionStatus::Draft) {
                throw ValidationException::withMessages([
                    'attendance_session' => 'Attendance session has already been finalized.',
                ]);
            }

            $eligibleStudentCount = LearningGroupStudent::query()
                ->where('learning_group_id', $session->learning_group_id_snapshot)
                ->whereDate('starts_at', '<=', $session->date)
                ->where(function ($query) use ($session) {
                    $query
                        ->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', $session->date);
                })
                ->count();

            $recordCount = $session->attendanceRecords()->count();

            if ($recordCount !== $eligibleStudentCount) {
                throw ValidationException::withMessages([
                    'attendance_session' => 'Attendance session does not contain a complete set of attendance records.',
                ]);
            }

            if ($recordCount === 0) {
                throw ValidationException::withMessages([
                    'attendance_session' => 'Attendance session has no attendance records.',
                ]);
            }

            $session->update([
                'status' => AttendanceSessionStatus::Finalized,
                'finalized_by' => Auth::id(),
                'finalized_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'domain' => 'attendance',
                'action' => 'finalize',
                'auditable_type' => AttendanceSession::class,
                'auditable_id' => $session->id,
                'old_values' => [
                    'status' => AttendanceSessionStatus::Draft->value,
                ],
                'new_values' => [
                    'status' => AttendanceSessionStatus::Finalized->value,
                    'finalized_by' => $session->finalized_by,
                    'finalized_at' => $session->finalized_at?->toISOString(),
                ],
            ]);

            return $session->refresh();
        });
    }
}
