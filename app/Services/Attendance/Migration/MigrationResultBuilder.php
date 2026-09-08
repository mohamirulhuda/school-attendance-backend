<?php

namespace App\Services\Attendance\Migration;

use App\Enums\AttendanceStatus;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use Carbon\Carbon;

class MigrationResultBuilder
{
    public function __construct(
        private readonly LegacyTeacherMapper $teacherMapper,
        private readonly LegacyStudentMapper $studentMapper,
        private readonly LegacyScheduleMapper $scheduleMapper,
    ) {
    }

    /**
     * @param array{
     *     source?: array,
     *     rows?: array<int, array>
     * } $legacy
     *
     * @param array<int|string, array{
     *     status: MigrationStatus,
     *     schedule_id?: int,
     *     period_id?: int
     * }> $overrides
     */
    public function build(array $legacy, array $overrides = []): MigrationResult
    {
        $source = $legacy['source'] ?? [];

        $rows = collect($legacy['rows'] ?? [])
            ->map(
                fn (array $row) => $this->buildRow(
                    $row,
                    $source,
                    $overrides[(int) $row['legacy_id']] ?? null,
                )
            )
            ->values();

        return new MigrationResult(
            source: $source,
            rows: $rows,
        );
    }

    private function buildRow(
        array $row,
        array $source,
        ?array $override,
    ): MigrationResultRow {
        $date = Carbon::parse($row['date']);

        $teacherMapping = $this->teacherMapper->map($row['teacher']);

        $learningGroup = $this->resolveLearningGroup(
            $source['learning_group'] ?? null,
        );

        $period = $this->resolvePeriod($row['jam_ke']);

        $scheduleMapping = null;

        if (
            $teacherMapping->isMatched()
            && $learningGroup !== null
            && $period !== null
        ) {
            $scheduleMapping = $this->scheduleMapper->map(
                learningGroupId: $learningGroup->id,
                teacherId: $teacherMapping->teacher->id,
                date: $date,
                periodId: $period->id,
            );
        }

        $schedule = $this->resolveSchedule(
            $scheduleMapping,
            $override,
        );

        $effectivePeriod = $this->resolveEffectivePeriod(
            $period,
            $override,
        );

        $migration = $this->classify(
            teacherMatched: $teacherMapping->isMatched(),
            learningGroup: $learningGroup,
            period: $period,
            scheduleMapping: $scheduleMapping,
            override: $override,
        );

        $attendanceSession = [
            'schedule_id' => $schedule?->id,
            'teacher_id_snapshot' => $teacherMapping->teacher?->id,
            'subject_id_snapshot' => $schedule?->subject_id,
            'learning_group_id_snapshot' => $learningGroup?->id,
            'period_id_snapshot' => $effectivePeriod?->id,
            'date' => $date->toDateString(),
        ];

        $attendanceRecords = collect($row['students'] ?? [])
            ->map(
                fn (string $status, string $studentName) =>
                $this->buildAttendanceRecord(
                    $studentName,
                    $status,
                )
            )
            ->values()
            ->all();

        return new MigrationResultRow(
            legacyId: (int) $row['legacy_id'],
            timestamp: $row['timestamp'],
            email: $row['email'],
            teacher: $row['teacher'],
            date: $row['date'],
            jamKe: $row['jam_ke'],
            migration: $migration,
            attendanceSession: $attendanceSession,
            attendanceRecords: $attendanceRecords,
        );
    }

    private function resolveSchedule(
        ?LegacyScheduleMappingResult $scheduleMapping,
        ?array $override,
    ): ?Schedule {
        if ($scheduleMapping?->schedule !== null) {
            return $scheduleMapping->schedule;
        }

        if ($override === null || ! isset($override['schedule_id'])) {
            return null;
        }

        return Schedule::query()->find($override['schedule_id']);
    }

    private function resolveEffectivePeriod(
        ?Period $period,
        ?array $override,
    ): ?Period {
        if ($override === null || ! isset($override['period_id'])) {
            return $period;
        }

        return Period::query()->find($override['period_id']);
    }

    private function buildAttendanceRecord(
        string $studentName,
        string $legacyStatus,
    ): array {
        $studentMapping = $this->studentMapper->map($studentName);

        $status = match (strtolower(trim($legacyStatus))) {
            'hadir' => AttendanceStatus::Present->value,
            'sakit' => AttendanceStatus::Sick->value,
            'izin' => AttendanceStatus::Excused->value,
            'alpha' => AttendanceStatus::Absent->value,
            default => null,
        };

        return [
            'student_id' => $studentMapping->student?->id,
            'student_name' => $studentName,
            'status' => $status,
            'note' => null,
        ];
    }

    private function resolveLearningGroup(
        ?string $legacyLearningGroup,
    ): ?LearningGroup {
        if ($legacyLearningGroup === null) {
            return null;
        }

        $name = preg_replace_callback(
            '/^(10|11|12)(?=\s)/',
            fn (array $matches): string => match ($matches[1]) {
                '10' => 'X',
                '11' => 'XI',
                '12' => 'XII',
            },
            trim($legacyLearningGroup),
        );

        return LearningGroup::query()
            ->where('name', $name)
            ->first();
    }

    private function resolvePeriod(string $legacyPeriod): ?Period
    {
        return match (trim($legacyPeriod)) {
            '1 - 2' => Period::query()->where('number', 1)->first(),
            '3 - 4' => Period::query()->where('number', 2)->first(),
            '5 - 6' => Period::query()->where('number', 3)->first(),
            '7 - 8' => Period::query()->where('number', 4)->first(),
            default => null,
        };
    }

    private function classify(
        bool $teacherMatched,
        ?LearningGroup $learningGroup,
        ?Period $period,
        ?LegacyScheduleMappingResult $scheduleMapping,
        ?array $override,
    ): array {
        if ($override !== null) {
            return [
                'status' => $override['status']->value,
                'note' => match ($override['status']) {
                    MigrationStatus::Match => 'Mapped successfully.',
                    MigrationStatus::HistoricalShift => sprintf(
                        'Historical shift. Reference schedule ID: %d.',
                        $override['schedule_id'],
                    ),
                    MigrationStatus::Skip => 'Anomaly requires manual review.',
                },
            ];
        }

        if (! $teacherMatched) {
            return [
                'status' => MigrationStatus::Skip->value,
                'note' => 'Anomaly requires manual review.',
            ];
        }

        if ($learningGroup === null || $period === null) {
            return [
                'status' => MigrationStatus::Skip->value,
                'note' => 'Anomaly requires manual review.',
            ];
        }

        if ($scheduleMapping?->isMatched()) {
            return [
                'status' => MigrationStatus::Match->value,
                'note' => 'Mapped successfully.',
            ];
        }

        return [
            'status' => MigrationStatus::Skip->value,
            'note' => 'Anomaly requires manual review.',
        ];
    }
}
