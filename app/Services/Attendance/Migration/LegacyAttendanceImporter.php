<?php

namespace App\Services\Attendance\Migration;

use App\Models\AttendanceSession;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class LegacyAttendanceImporter
{
    public function __construct(
        private readonly CreateHistoricalAttendanceSession $createHistoricalAttendanceSession,
    ) {
    }

    /**
     * Import migration result into attendance history.
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     skipped: int,
     * }
     */
    public function import(MigrationResult $result, int $userId): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($result->rows as $row) {
            $status = MigrationStatus::from(
                $row->migration['status']
            );

            if ($status === MigrationStatus::Skip) {
                $skipped++;

                continue;
            }

            $existingSession = $this->findHistoricalSession(
                $row->legacyId,
            );

            if ($existingSession !== null) {
                $this->updateHistoricalAttendance(
                    $existingSession,
                    $row,
                    $userId,
                );

                $updated++;

                continue;
            }

            $this->createHistoricalAttendanceSession->execute(
                [
                    ...$row->attendanceSession,
                    'attendance_records' => $row->attendanceRecords,
                    'legacy_id' => $row->legacyId,
                ],
                $userId,
            );

            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function findHistoricalSession(
        int|string $legacyId,
    ): ?AttendanceSession {
        $audit = AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'create_historical')
            ->where('auditable_type', AttendanceSession::class)
            ->where('new_values->legacy_id', $legacyId)
            ->latest('id')
            ->first();

        if ($audit === null) {
            return null;
        }

        return AttendanceSession::query()->find($audit->auditable_id);
    }

    private function updateHistoricalAttendance(
        AttendanceSession $session,
        MigrationResultRow $row,
        int $userId,
    ): void {
        DB::transaction(function () use ($session, $row, $userId) {
            $oldValues = [
                'status' => $session->status->value,
                'schedule_id' => $session->schedule_id,
                'teacher_id_snapshot' => $session->teacher_id_snapshot,
                'subject_id_snapshot' => $session->subject_id_snapshot,
                'learning_group_id_snapshot' => $session->learning_group_id_snapshot,
                'period_id_snapshot' => $session->period_id_snapshot,
                'date' => $session->date->toDateString(),
            ];

            $session->update([
                'schedule_id' => $row->attendanceSession['schedule_id'],
                'teacher_id_snapshot' => $row->attendanceSession['teacher_id_snapshot'],
                'subject_id_snapshot' => $row->attendanceSession['subject_id_snapshot'],
                'learning_group_id_snapshot' => $row->attendanceSession['learning_group_id_snapshot'],
                'period_id_snapshot' => $row->attendanceSession['period_id_snapshot'],
                'date' => $row->attendanceSession['date'],
            ]);

            $existingRecords = $session->attendanceRecords()
                ->get()
                ->keyBy('student_id');

            $incomingStudentIds = [];

            foreach ($row->attendanceRecords as $record) {
                $studentId = $record['student_id'];
                $incomingStudentIds[] = $studentId;

                if ($existingRecords->has($studentId)) {
                    $existingRecords[$studentId]->update([
                        'status' => $record['status'],
                        'note' => $record['note'],
                    ]);

                    continue;
                }

                $session->attendanceRecords()->create([
                    'student_id' => $studentId,
                    'status' => $record['status'],
                    'note' => $record['note'],
                ]);
            }

            $session->attendanceRecords()
                ->whereNotIn('student_id', $incomingStudentIds)
                ->delete();

            AuditLog::query()->create([
                'user_id' => $userId,
                'domain' => 'attendance',
                'action' => 'update_historical',
                'auditable_type' => AttendanceSession::class,
                'auditable_id' => $session->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'status' => $session->status->value,
                    'schedule_id' => $session->schedule_id,
                    'teacher_id_snapshot' => $session->teacher_id_snapshot,
                    'subject_id_snapshot' => $session->subject_id_snapshot,
                    'learning_group_id_snapshot' => $session->learning_group_id_snapshot,
                    'period_id_snapshot' => $session->period_id_snapshot,
                    'date' => $session->date->toDateString(),
                    'legacy_id' => $row->legacyId,
                ],
            ]);
        });
    }
}
