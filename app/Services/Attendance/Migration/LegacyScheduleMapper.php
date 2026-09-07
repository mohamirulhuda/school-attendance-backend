<?php

namespace App\Services\Attendance\Migration;

use App\Models\Schedule;
use Carbon\CarbonInterface;

class LegacyScheduleMapper
{
    public function map(
        int $learningGroupId,
        int $teacherId,
        CarbonInterface $date,
        int $periodId,
    ): LegacyScheduleMappingResult {
        $dayOfWeek = $date->dayOfWeekIso;

        $schedules = Schedule::query()
            ->where('learning_group_id', $learningGroupId)
            ->where('teacher_id', $teacherId)
            ->where('period_id', $periodId)
            ->where('day_of_week', $dayOfWeek)
            ->get();

        return match ($schedules->count()) {
            0 => new LegacyScheduleMappingResult(
                schedule: null,
                status: 'not_found',
            ),

            1 => new LegacyScheduleMappingResult(
                schedule: $schedules->first(),
                status: 'match',
            ),

            default => new LegacyScheduleMappingResult(
                schedule: null,
                status: 'ambiguous',
            ),
        };
    }
}
