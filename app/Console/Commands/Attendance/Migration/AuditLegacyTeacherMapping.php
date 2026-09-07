<?php

namespace App\Console\Commands\Attendance\Migration;

use App\Services\Attendance\Migration\LegacyAttendanceReader;
use App\Services\Attendance\Migration\LegacyTeacherMapper;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:audit-legacy-teacher-mapping')]
#[Description('Audit legacy teacher mapping against the current teacher master data')]
class AuditLegacyTeacherMapping extends Command
{
    public function handle(
        LegacyAttendanceReader $reader,
        LegacyTeacherMapper $mapper,
    ): int {
        $legacy = $reader->read('legacy_attendance_10_c_2026.json');

        $legacyTeachers = collect($legacy['rows'])
            ->pluck('teacher')
            ->unique()
            ->values();

        $results = $legacyTeachers->map(
            fn (string $legacyTeacher) => $mapper->map($legacyTeacher)
        );

        $statusCounts = $results->countBy('status');

        $this->newLine();
        $this->info('Legacy Teacher Mapping Audit');
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
            $this->warn('Teachers requiring manual review:');

            $this->table(
                ['Legacy Teacher', 'Status'],
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

        $this->info('All legacy teachers mapped successfully.');

        return self::SUCCESS;
    }
}
