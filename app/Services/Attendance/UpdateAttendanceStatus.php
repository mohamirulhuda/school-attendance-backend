<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAttendanceStatus
{
    public function execute(
        int $attendanceRecordId,
        AttendanceStatus $status,
        ?string $note = null,
    ): AttendanceRecord {
        return DB::transaction(function () use (
            $attendanceRecordId,
            $status,
            $note,
        ) {
            $record = AttendanceRecord::query()
                ->with('attendanceSession')
                ->lockForUpdate()
                ->findOrFail($attendanceRecordId);

            if (
                $record->attendanceSession->status !==
                AttendanceSessionStatus::Draft
            ) {
                throw ValidationException::withMessages([
                    'attendance_record' => 'Attendance record cannot be updated after the session is finalized.',
                ]);
            }

            $oldValues = [
                'status' => $record->status->value,
                'note' => $record->note,
            ];

            $record->update([
                'status' => $status,
                'note' => $note,
            ]);

            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'domain' => 'attendance',
                'action' => 'update_status',
                'auditable_type' => AttendanceRecord::class,
                'auditable_id' => $record->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'status' => $record->status->value,
                    'note' => $record->note,
                ],
            ]);

            return $record->refresh();
        });
    }
}
