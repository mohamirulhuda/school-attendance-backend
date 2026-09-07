<?php

namespace App\Services\Attendance\Migration;

class MigrationResultRow
{
    public function __construct(
        public readonly int $legacyId,
        public readonly string $timestamp,
        public readonly string $email,
        public readonly string $teacher,
        public readonly string $date,
        public readonly string $jamKe,
        public readonly array $migration,
        public readonly array $attendanceSession,
        public readonly array $attendanceRecords,
    ) {
    }

    public function toArray(): array
    {
        return [
            'legacy_id' => $this->legacyId,
            'timestamp' => $this->timestamp,
            'email' => $this->email,
            'teacher' => $this->teacher,
            'date' => $this->date,
            'jam_ke' => $this->jamKe,
            'migration' => $this->migration,
            'attendance_session' => $this->attendanceSession,
            'attendance_records' => $this->attendanceRecords,
        ];
    }
}
