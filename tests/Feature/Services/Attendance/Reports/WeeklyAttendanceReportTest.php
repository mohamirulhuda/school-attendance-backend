<?php

namespace Tests\Feature\Services\Attendance\Reports;

use App\Enums\AttendanceStatus;
use App\Services\Attendance\FinalizeAttendanceSession;
use App\Services\Attendance\StartAttendanceSession;
use App\Services\Attendance\UpdateAttendanceStatus;
use App\Services\Attendance\Reports\WeeklyAttendanceReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_finalized_attendance_by_academic_week(): void
    {
        $data = createAttendanceTestData();

        $this->actingAs($data['user']);

        $service = app(WeeklyAttendanceReport::class);

        $result = $service->execute(
            Carbon::parse('2026-09-01'),
            $data['learningGroup']->id,
            $data['teacher']->id,
            $data['subject']->id,
        );

        $this->assertCount(5, $result);

        $week = $result->firstWhere('week', 1);

        $this->assertNotNull($week);
        $this->assertSame('2026-08-29', $week['start_date']);
        $this->assertSame('2026-09-03', $week['end_date']);
    }

    public function test_it_aggregates_attendance_statuses_per_student(): void
    {
        $data = createAttendanceTestData();

        $this->actingAs($data['user']);

        $session = app(StartAttendanceSession::class)
            ->execute(
                $data['schedule']->id,
                Carbon::parse('2026-09-05'),
            );

        app(UpdateAttendanceStatus::class)
            ->execute(
                $session->attendanceRecords->firstWhere(
                    'student_id',
                    $data['students'][0]->id,
                )->id,
                AttendanceStatus::Sick,
            );

        app(UpdateAttendanceStatus::class)
            ->execute(
                $session->attendanceRecords->firstWhere(
                    'student_id',
                    $data['students'][1]->id,
                )->id,
                AttendanceStatus::Absent,
            );

        app(FinalizeAttendanceSession::class)
            ->execute($session->id);

        $service = app(WeeklyAttendanceReport::class);

        $result = $service->execute(
            Carbon::parse('2026-09-01'),
            $data['learningGroup']->id,
            $data['teacher']->id,
            $data['subject']->id,
        );

        $week = $result->firstWhere('week', 2);

        $this->assertCount(2, $week['students']);

        $student1 = collect($week['students'])
            ->firstWhere('student_id', $data['students'][0]->id);

        $student2 = collect($week['students'])
            ->firstWhere('student_id', $data['students'][1]->id);

        $this->assertSame(0, $student1['present']);
        $this->assertSame(1, $student1['sick']);
        $this->assertSame(0, $student1['excused']);
        $this->assertSame(0, $student1['absent']);
        $this->assertSame(1, $student1['total']);

        $this->assertSame(0, $student2['present']);
        $this->assertSame(0, $student2['sick']);
        $this->assertSame(0, $student2['excused']);
        $this->assertSame(1, $student2['absent']);
        $this->assertSame(1, $student2['total']);
    }

    public function test_it_returns_empty_students_for_weeks_without_attendance(): void
    {
        $data = createAttendanceTestData();

        $this->actingAs($data['user']);

        $service = app(WeeklyAttendanceReport::class);

        $result = $service->execute(
            Carbon::parse('2026-09-01'),
            $data['learningGroup']->id,
            $data['teacher']->id,
            $data['subject']->id,
        );

        $this->assertCount(5, $result);

        $week1 = $result->firstWhere('week', 1);
        $week5 = $result->firstWhere('week', 5);

        $this->assertSame([], $week1['students']);
        $this->assertSame([], $week5['students']);
    }

    public function test_it_excludes_draft_sessions_and_attendance_outside_requested_month(): void
    {
        $data = createAttendanceTestData();

        $this->actingAs($data['user']);

        $draftSession = app(StartAttendanceSession::class)
            ->execute(
                $data['schedule']->id,
                Carbon::parse('2026-09-05'),
            );

        $previousMonthSession = app(StartAttendanceSession::class)
            ->execute(
                $data['schedule']->id,
                Carbon::parse('2026-08-29'),
            );

        app(FinalizeAttendanceSession::class)
            ->execute($previousMonthSession->id);

        $result = app(WeeklyAttendanceReport::class)->execute(
            Carbon::parse('2026-09-01'),
            $data['learningGroup']->id,
            $data['teacher']->id,
            $data['subject']->id,
        );

        $this->assertCount(5, $result);

        foreach ($result as $week) {
            $this->assertSame([], $week['students']);
        }

        $this->assertSame(
            \App\Enums\AttendanceSessionStatus::Draft,
            $draftSession->fresh()->status,
        );
    }
}
