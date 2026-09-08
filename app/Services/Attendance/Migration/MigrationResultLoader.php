<?php

namespace App\Services\Attendance\Migration;

use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

class MigrationResultLoader
{
    /**
     * Load a migration result artifact from JSON.
     *
     * @throws JsonException
     */
    public function load(string $path): MigrationResult
    {
        if (! File::exists($path)) {
            throw new RuntimeException(
                "Migration result file not found: {$path}"
            );
        }

        $data = json_decode(
            File::get($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $rows = collect($data['rows'] ?? [])
            ->map(
                fn (array $row) => new MigrationResultRow(
                    legacyId: (int) $row['legacy_id'],
                    timestamp: $row['timestamp'],
                    email: $row['email'],
                    teacher: $row['teacher'],
                    date: $row['date'],
                    jamKe: $row['jam_ke'],
                    migration: $row['migration'],
                    attendanceSession: $row['attendance_session'],
                    attendanceRecords: $row['attendance_records'],
                )
            )
            ->values();

        return new MigrationResult(
            source: $data['source'] ?? [],
            rows: $rows,
        );
    }
}
