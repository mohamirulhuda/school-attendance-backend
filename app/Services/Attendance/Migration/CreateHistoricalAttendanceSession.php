<?php

namespace App\Services\Attendance\Migration;

use App\Enums\AttendanceSessionStatus;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateHistoricalAttendanceSession
{
    /**
     * Create a finalized historical attendance session from migration data.
     *
     * @param array{
     *     schedule_id:int,
     *     teacher_id_snapshot:int,
     *     subject_id_snapshot:int,
     *     learning_group_id_snapshot:int,
     *     period_id_snapshot:int,
     *     date:\Carbon\CarbonInterface|string,
     *     attendance_records:list<array{
     *         student_id:int,
     *         status:\App\Enums\AttendanceStatus|string,
     *         note:string|null
     *     }>,
     *     legacy_id:int|string|null
     * } $data
     */
    public function execute(array $data): AttendanceSession
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new InvalidArgumentException(
                'An authenticated user is required to create historical attendance.'
            );
        }

        return DB::transaction(function () use ($data, $userId) {
            $session = AttendanceSession::query()->create([
                'schedule_id' => $data['schedule_id'],
                'teacher_id_snapshot' => $data['teacher_id_snapshot'],
                'subject_id_snapshot' => $data['subject_id_snapshot'],
                'learning_group_id_snapshot' => $data['learning_group_id_snapshot'],
                'period_id_snapshot' => $data['period_id_snapshot'],
                'date' => $data['date'],
                'status' => AttendanceSessionStatus::Finalized,
                'opened_at' => null,
                'finalized_by' => $userId,
                'finalized_at' => now(),
            ]);

            foreach ($data['attendance_records'] as $record) {
                $session->attendanceRecords()->create([
                    'student_id' => $record['student_id'],
                    'status' => $record['status'],
                    'note' => $record['note'] ?? null,
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $userId,
                'domain' => 'attendance',
                'action' => 'create_historical',
                'auditable_type' => AttendanceSession::class,
                'auditable_id' => $session->id,
                'old_values' => null,
                'new_values' => [
                    'status' => AttendanceSessionStatus::Finalized->value,
                    'schedule_id' => $session->schedule_id,
                    'teacher_id_snapshot' => $session->teacher_id_snapshot,
                    'subject_id_snapshot' => $session->subject_id_snapshot,
                    'learning_group_id_snapshot' => $session->learning_group_id_snapshot,
                    'period_id_snapshot' => $session->period_id_snapshot,
                    'date' => $session->date->toDateString(),
                    'finalized_by' => $session->finalized_by,
                    'finalized_at' => $session->finalized_at?->toISOString(),
                    'legacy_id' => $data['legacy_id'] ?? null,
                ],
            ]);

            return $session->refresh();
        });
    }
}
