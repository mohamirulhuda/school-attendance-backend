<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Services\Attendance\FinalizeAttendanceSession;
use App\Services\Attendance\Reports\WeeklyAttendanceReport;
use App\Services\Attendance\StartAttendanceSession;
use App\Services\Attendance\UpdateAttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aggregates finalized attendance by academic week', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $service = app(WeeklyAttendanceReport::class);

    $result = $service->execute(
        Carbon::parse('2026-09-01'),
        $data['learningGroup']->id,
        $data['teacher']->id,
        $data['subject']->id,
    );

    expect($result)->toHaveCount(5);

    $week = $result->firstWhere('week', 1);

    expect($week)->not->toBeNull();
    expect($week['start_date'])->toBe('2026-08-29');
    expect($week['end_date'])->toBe('2026-09-03');
});

it('aggregates attendance statuses per student', function () {
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

    expect($week['students'])->toHaveCount(2);

    $student1 = collect($week['students'])
        ->firstWhere('student_id', $data['students'][0]->id);

    $student2 = collect($week['students'])
        ->firstWhere('student_id', $data['students'][1]->id);

    expect($student1['present'])->toBe(0);
    expect($student1['sick'])->toBe(1);
    expect($student1['excused'])->toBe(0);
    expect($student1['absent'])->toBe(0);
    expect($student1['total'])->toBe(1);

    expect($student2['present'])->toBe(0);
    expect($student2['sick'])->toBe(0);
    expect($student2['excused'])->toBe(0);
    expect($student2['absent'])->toBe(1);
    expect($student2['total'])->toBe(1);
});

it('returns empty students for weeks without attendance', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $service = app(WeeklyAttendanceReport::class);

    $result = $service->execute(
        Carbon::parse('2026-09-01'),
        $data['learningGroup']->id,
        $data['teacher']->id,
        $data['subject']->id,
    );

    expect($result)->toHaveCount(5);

    $week1 = $result->firstWhere('week', 1);
    $week5 = $result->firstWhere('week', 5);

    expect($week1['students'])->toBe([]);
    expect($week5['students'])->toBe([]);
});

it('excludes draft sessions and attendance outside requested month', function () {
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

    expect($result)->toHaveCount(5);

    foreach ($result as $week) {
        expect($week['students'])->toBe([]);
    }

    expect($draftSession->fresh()->status)
        ->toBe(AttendanceSessionStatus::Draft);
});
