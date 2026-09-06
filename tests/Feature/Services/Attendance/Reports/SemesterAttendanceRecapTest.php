<?php


use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Services\Attendance\FinalizeAttendanceSession;
use App\Services\Attendance\Reports\SemesterAttendanceRecap;
use App\Services\Attendance\StartAttendanceSession;
use App\Services\Attendance\UpdateAttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aggregates finalized attendance within the academic period', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute($data['academicPeriod']->id);

    expect($result)->toHaveCount(2);

    $student = $result->firstWhere(
        'student_id',
        $data['students'][0]->id,
    );

    expect($student)->not->toBeNull()
        ->and($student['present'])->toBe(1)
        ->and($student['sick'])->toBe(0)
        ->and($student['excused'])->toBe(0)
        ->and($student['absent'])->toBe(0)
        ->and($student['total'])->toBe(1);
});

it('excludes draft sessions', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    $result = app(SemesterAttendanceRecap::class)
        ->execute($data['academicPeriod']->id);

    expect($result)->toBeEmpty();
});

it('aggregates all attendance statuses per student', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    $student1Record = $session->attendanceRecords->firstWhere(
        'student_id',
        $data['students'][0]->id,
    );

    $student2Record = $session->attendanceRecords->firstWhere(
        'student_id',
        $data['students'][1]->id,
    );

    app(UpdateAttendanceStatus::class)
        ->execute(
            $student1Record->id,
            AttendanceStatus::Sick,
        );

    app(UpdateAttendanceStatus::class)
        ->execute(
            $student2Record->id,
            AttendanceStatus::Absent,
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute($data['academicPeriod']->id);

    $student1 = $result->firstWhere(
        'student_id',
        $data['students'][0]->id,
    );

    $student2 = $result->firstWhere(
        'student_id',
        $data['students'][1]->id,
    );

    expect($student1['present'])->toBe(0)
        ->and($student1['sick'])->toBe(1)
        ->and($student1['excused'])->toBe(0)
        ->and($student1['absent'])->toBe(0)
        ->and($student1['total'])->toBe(1);

    expect($student2['present'])->toBe(0)
        ->and($student2['sick'])->toBe(0)
        ->and($student2['excused'])->toBe(0)
        ->and($student2['absent'])->toBe(1)
        ->and($student2['total'])->toBe(1);
});

it('excludes attendance from another academic period', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $otherAcademicPeriod = \App\Models\AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => \App\Enums\Semester::Even,
        'starts_at' => '2027-01-01',
        'ends_at' => '2027-06-30',
        'is_active' => true,
    ]);

    $otherLearningGroup = \App\Models\LearningGroup::create([
        'academic_period_id' => $otherAcademicPeriod->id,
        'name' => 'Other Test Class',
        'code' => 'OTHER',
        'is_active' => true,
    ]);

    \App\Models\LearningGroupStudent::create([
        'learning_group_id' => $otherLearningGroup->id,
        'student_id' => $data['students'][0]->id,
        'starts_at' => '2027-01-01',
        'ends_at' => '2027-06-30',
    ]);

    $otherSchedule = \App\Models\Schedule::create([
        'academic_period_id' => $otherAcademicPeriod->id,
        'learning_group_id' => $otherLearningGroup->id,
        'subject_id' => $data['subject']->id,
        'teacher_id' => $data['teacher']->id,
        'period_id' => $data['period']->id,
        'day_of_week' => 1,
    ]);

    $session = app(StartAttendanceSession::class)
        ->execute(
            $otherSchedule->id,
            Carbon::parse('2027-01-04'),
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute($data['academicPeriod']->id);

    expect($result)->toBeEmpty();
});

it('filters by learning group', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute(
            $data['academicPeriod']->id,
            $data['learningGroup']->id,
        );

    expect($result)->toHaveCount(2);
});

it('filters by teacher', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute(
            $data['academicPeriod']->id,
            null,
            $data['teacher']->id,
        );

    expect($result)->toHaveCount(2);
});

it('filters by subject', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute(
            $data['academicPeriod']->id,
            null,
            null,
            $data['subject']->id,
        );

    expect($result)->toHaveCount(2);
});

it('returns an empty collection when the academic period has no finalized attendance', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $result = app(SemesterAttendanceRecap::class)
        ->execute($data['academicPeriod']->id);

    expect($result)->toBeEmpty();
});

it('calculates attendance percentage per student', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session1 = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-07'),
        );

    $student1Record = $session1->attendanceRecords->firstWhere(
        'student_id',
        $data['students'][0]->id,
    );

    app(UpdateAttendanceStatus::class)
        ->execute(
            $student1Record->id,
            AttendanceStatus::Present,
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session1->id);

    $session2 = app(StartAttendanceSession::class)
        ->execute(
            $data['schedule']->id,
            Carbon::parse('2026-09-14'),
        );

    $student1Record = $session2->attendanceRecords->firstWhere(
        'student_id',
        $data['students'][0]->id,
    );

    app(UpdateAttendanceStatus::class)
        ->execute(
            $student1Record->id,
            AttendanceStatus::Absent,
        );

    app(FinalizeAttendanceSession::class)
        ->execute($session2->id);

    $result = app(SemesterAttendanceRecap::class)
        ->execute($data['academicPeriod']->id);

    $student = $result->firstWhere(
        'student_id',
        $data['students'][0]->id,
    );

    expect($student['present'])->toBe(1)
        ->and($student['absent'])->toBe(1)
        ->and($student['total'])->toBe(2)
        ->and($student['attendance_percentage'])->toBe(50.0);
});
