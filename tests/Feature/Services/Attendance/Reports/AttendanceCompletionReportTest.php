<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Attendance\Reports\AttendanceCompletionReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all expected schedules on the requested date', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $otherPeriod = Period::create([
        'number' => 100,
        'name' => 'Other Period',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    $scheduleTwo = Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $schedule->learning_group_id,
        'subject_id' => $schedule->subject_id,
        'teacher_id' => $schedule->teacher_id,
        'period_id' => $otherPeriod->id,
        'day_of_week' => $date->dayOfWeekIso,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(2);

    expect($report->pluck('schedule_id')->sort()->values()->all())
        ->toBe([
            $schedule->id,
            $scheduleTwo->id,
        ]);
});

it('marks a schedule as filled when an attendance session exists', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $this->actingAs($user);

    AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => $date,
        'status' => AttendanceSessionStatus::Draft,
        'opened_at' => now(),
        'finalized_by' => null,
        'finalized_at' => null,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(1);

    expect($report[0])->toMatchArray([
        'schedule_id' => $schedule->id,
        'date' => '2026-09-07',
        'teacher_id' => $schedule->teacher_id,
        'subject_id' => $schedule->subject_id,
        'learning_group_id' => $schedule->learning_group_id,
        'period_id' => $schedule->period_id,
        'status' => 'filled',
    ]);
});

it('marks a schedule as unfilled when attendance session does not exist', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(1);

    expect($report[0])->toMatchArray([
        'schedule_id' => $schedule->id,
        'date' => '2026-09-07',
        'teacher_id' => $schedule->teacher_id,
        'subject_id' => $schedule->subject_id,
        'learning_group_id' => $schedule->learning_group_id,
        'period_id' => $schedule->period_id,
        'status' => 'unfilled',
    ]);
});

it('excludes schedules on a different day', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $otherPeriod = Period::create([
        'number' => 100,
        'name' => 'Other Period',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $schedule->learning_group_id,
        'subject_id' => $schedule->subject_id,
        'teacher_id' => $schedule->teacher_id,
        'period_id' => $otherPeriod->id,
        'day_of_week' => 2,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(1);
    expect($report[0]['schedule_id'])->toBe($schedule->id);
});

it('excludes schedules outside the academic period', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $otherAcademicPeriod = AcademicPeriod::create([
        'academic_year' => '2025/2026',
        'semester' => Semester::Even,
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-06-30',
        'is_active' => false,
    ]);

    $otherLearningGroup = LearningGroup::create([
        'academic_period_id' => $otherAcademicPeriod->id,
        'name' => 'Other Group',
        'code' => 'OTHER',
        'description' => null,
        'is_active' => true,
    ]);

    $otherPeriod = Period::create([
        'number' => 100,
        'name' => 'Other Period',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    Schedule::create([
        'academic_period_id' => $otherAcademicPeriod->id,
        'learning_group_id' => $otherLearningGroup->id,
        'subject_id' => $schedule->subject_id,
        'teacher_id' => $schedule->teacher_id,
        'period_id' => $otherPeriod->id,
        'day_of_week' => $date->dayOfWeekIso,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(1);
    expect($report[0]['schedule_id'])->toBe($schedule->id);
});

it('filters schedules by learning group', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $otherLearningGroup = LearningGroup::create([
        'academic_period_id' => $schedule->academic_period_id,
        'name' => 'Other Group',
        'code' => 'OTHER',
        'description' => null,
        'is_active' => true,
    ]);

    $otherPeriod = Period::create([
        'number' => 100,
        'name' => 'Other Period',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    $otherSchedule = Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $otherLearningGroup->id,
        'subject_id' => $schedule->subject_id,
        'teacher_id' => $schedule->teacher_id,
        'period_id' => $otherPeriod->id,
        'day_of_week' => $date->dayOfWeekIso,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute(
        $date,
        learningGroupId: $otherLearningGroup->id,
    );

    expect($report)->toHaveCount(1);
    expect($report[0]['schedule_id'])->toBe($otherSchedule->id);
});

it('filters schedules by teacher', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $otherTeacher = Teacher::create([
        'name' => 'Other Teacher',
        'email' => 'other.teacher@example.com',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $otherPeriod = Period::create([
        'number' => 100,
        'name' => 'Other Period',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    $otherSchedule = Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $schedule->learning_group_id,
        'subject_id' => $schedule->subject_id,
        'teacher_id' => $otherTeacher->id,
        'period_id' => $otherPeriod->id,
        'day_of_week' => $date->dayOfWeekIso,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute(
        $date,
        teacherId: $otherTeacher->id,
    );

    expect($report)->toHaveCount(1);
    expect($report[0]['schedule_id'])->toBe($otherSchedule->id);
});

it('filters schedules by subject', function () {
    $data = createAttendanceTestData();

    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $otherSubject = Subject::create([
        'name' => 'Other Subject',
        'code' => 'OTHER',
        'is_active' => true,
    ]);

    $otherPeriod = Period::create([
        'number' => 100,
        'name' => 'Other Period',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    $otherSchedule = Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $schedule->learning_group_id,
        'subject_id' => $otherSubject->id,
        'teacher_id' => $schedule->teacher_id,
        'period_id' => $otherPeriod->id,
        'day_of_week' => $date->dayOfWeekIso,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute(
        $date,
        subjectId: $otherSubject->id,
    );

    expect($report)->toHaveCount(1);
    expect($report[0]['schedule_id'])->toBe($otherSchedule->id);
});

it('counts draft attendance session as filled', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $date = Carbon::parse('2026-09-07');

    $this->actingAs($user);

    AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => $date,
        'status' => AttendanceSessionStatus::Draft,
        'opened_at' => now(),
        'finalized_by' => null,
        'finalized_at' => null,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(1);
    expect($report[0]['status'])->toBe('filled');
});

it('does not use attendance record status as completion basis', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $student = $data['students'][0];
    $date = Carbon::parse('2026-09-07');

    $this->actingAs($user);

    $session = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => $date,
        'status' => AttendanceSessionStatus::Draft,
        'opened_at' => now(),
        'finalized_by' => null,
        'finalized_at' => null,
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'status' => AttendanceStatus::Absent,
    ]);

    $report = app(AttendanceCompletionReport::class)->execute($date);

    expect($report)->toHaveCount(1);
    expect($report[0]['status'])->toBe('filled');
});
