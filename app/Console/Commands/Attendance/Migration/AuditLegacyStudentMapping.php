<?php

namespace App\Console\Commands\Attendance\Migration;

use App\Services\Attendance\Migration\LegacyAttendanceReader;
use App\Services\Attendance\Migration\LegacyStudentMapper;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:audit-legacy-student-mapping')]
#[Description('Audit legacy student mapping against the current student master data')]
class AuditLegacyStudentMapping extends Command
{
    public function handle(
        LegacyAttendanceReader $reader,
        LegacyStudentMapper $mapper,
    ): int {
        $legacy = $reader->read('legacy_attendance_10_c_2026.json');

        $legacyStudents = collect($legacy['rows'])
            ->flatMap(
                fn (array $row) => array_keys($row['students'])
            )
            ->unique()
            ->values();

        $results = $legacyStudents->map(
            fn (string $legacyStudent) => $mapper->map($legacyStudent)
        );

        $statusCounts = $results->countBy('status');

        $this->newLine();
        $this->info('Legacy Student Mapping Audit');
        $this->newLine();

        $this->table(
            ['Status', 'Total'],
            [
                ['MATCH', $statusCounts->get('match', 0)],
                ['NOT_FOUND', $statusCounts->get('not_found', 0)],
                ['AMBIGUOUS', $statusCounts->get('ambiguous', 0)],
            ]
        );

        $this->newLine();

        $unmatched = $results->filter(
            fn ($result) => ! $result->isMatched()
        );

        if ($unmatched->isNotEmpty()) {
            $this->warn('Students requiring manual review:');

            $this->table(
                ['Legacy Student', 'Status'],
                $unmatched->map(
                    fn ($result) => [
                        $result->legacyName,
                        strtoupper($result->status),
                    ]
                )->all()
            );

            $this->newLine();

            return self::FAILURE;
        }

        $this->info('All legacy students mapped successfully.');

        return self::SUCCESS;
    }
}
