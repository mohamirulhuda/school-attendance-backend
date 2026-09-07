<?php

namespace App\Console\Commands\Attendance\Migration;

use App\Models\LearningGroup;
use App\Models\Period;
use App\Services\Attendance\Migration\LegacyAttendanceReader;
use App\Services\Attendance\Migration\LegacyScheduleMapper;
use App\Services\Attendance\Migration\LegacyTeacherMapper;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:audit-legacy-schedule-mapping')]
#[Description('Audit legacy schedule mapping against the current schedule master data')]
class AuditLegacyScheduleMapping extends Command
{
    public function handle(
        LegacyAttendanceReader $reader,
        LegacyTeacherMapper $teacherMapper,
        LegacyScheduleMapper $scheduleMapper,
    ): int {
        $legacy = $reader->read('legacy_attendance_10_c_2026.json');

        $legacyLearningGroup = $legacy['source']['learning_group'];

        $learningGroupName = preg_replace(
            '/^10(?=\s)/',
            'X',
            $legacyLearningGroup
        );

        $learningGroup = LearningGroup::query()
            ->where('name', $learningGroupName)
            ->first();

        if (! $learningGroup) {
            $this->error(
                "Learning group not found: {$legacy['source']['learning_group']}"
            );

            return self::FAILURE;
        }

        $periods = Period::query()
            ->get()
            ->keyBy('number');

        $results = collect($legacy['rows'])->map(
            function (array $row) use (
                $learningGroup,
                $periods,
                $teacherMapper,
                $scheduleMapper,
            ): array {
                $teacherResult = $teacherMapper->map($row['teacher']);

                if (! $teacherResult->isMatched()) {
                    return [
                        'legacy_id' => $row['legacy_id'],
                        'date' => $row['date'],
                        'jam_ke' => $row['jam_ke'],
                        'teacher' => $row['teacher'],
                        'status' => strtoupper($teacherResult->status),
                        'schedule' => null,
                    ];
                }

                $periodNumber = match ($row['jam_ke']) {
                    '1 - 2' => 1,
                    '3 - 4' => 2,
                    '5 - 6' => 3,
                    '7 - 8' => 4,
                    default => null,
                };

                if ($periodNumber === null) {
                    return [
                        'legacy_id' => $row['legacy_id'],
                        'date' => $row['date'],
                        'jam_ke' => $row['jam_ke'],
                        'teacher' => $row['teacher'],
                        'status' => 'INVALID_PERIOD',
                        'schedule' => null,
                    ];
                }

                $period = $periods->get($periodNumber);

                if (! $period) {
                    return [
                        'legacy_id' => $row['legacy_id'],
                        'date' => $row['date'],
                        'jam_ke' => $row['jam_ke'],
                        'teacher' => $row['teacher'],
                        'status' => 'PERIOD_NOT_FOUND',
                        'schedule' => null,
                    ];
                }

                $scheduleResult = $scheduleMapper->map(
                    learningGroupId: $learningGroup->id,
                    teacherId: $teacherResult->teacher->id,
                    date: Carbon::parse($row['date']),
                    periodId: $period->id,
                );

                return [
                    'legacy_id' => $row['legacy_id'],
                    'date' => $row['date'],
                    'jam_ke' => $row['jam_ke'],
                    'teacher' => $row['teacher'],
                    'status' => strtoupper($scheduleResult->status),
                    'schedule' => $scheduleResult->schedule,
                ];
            }
        );

        $statusCounts = $results->countBy('status');

        $this->newLine();
        $this->info('Legacy Schedule Mapping Audit');
        $this->newLine();

        $this->table(
            ['Status', 'Total'],
            [
                ['MATCH', $statusCounts->get('MATCH', 0)],
                ['NOT_FOUND', $statusCounts->get('NOT_FOUND', 0)],
                ['AMBIGUOUS', $statusCounts->get('AMBIGUOUS', 0)],
            ]
        );

        $this->newLine();

        $unmatched = $results->filter(
            fn (array $result) => $result['status'] !== 'MATCH'
        );

        if ($unmatched->isNotEmpty()) {
            $this->warn('Rows requiring manual review:');

            $this->table(
                ['Legacy ID', 'Date', 'Jam Ke', 'Teacher', 'Status'],
                $unmatched->map(
                    fn (array $result) => [
                        $result['legacy_id'],
                        $result['date'],
                        $result['jam_ke'],
                        $result['teacher'],
                        $result['status'],
                    ]
                )->all()
            );

            $this->newLine();

            return self::FAILURE;
        }

        $this->info('All legacy schedules mapped successfully.');

        return self::SUCCESS;
    }
}
