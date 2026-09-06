<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Attendance\Reports\MonthlyAttendanceRecap;

uses(RefreshDatabase::class);

it('recaps attendance statuses per student for finalized sessions', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $students = $data['students'];

    $this->actingAs($user);

    $sessionOne = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-01',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    $sessionTwo = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-02',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionOne->id,
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Present,
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionOne->id,
        'student_id' => $students[1]->id,
        'status' => AttendanceStatus::Sick,
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionTwo->id,
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Excused,
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionTwo->id,
        'student_id' => $students[1]->id,
        'status' => AttendanceStatus::Absent,
    ]);

    $report = app(MonthlyAttendanceRecap::class)->execute(
        Carbon::parse('2026-09-01'),
    );

    expect($report)->toHaveCount(2);

    expect($report[0])->toBe([
        'student_id' => $students[0]->id,
        'student_name' => $students[0]->name,
        'present' => 1,
        'sick' => 0,
        'excused' => 1,
        'absent' => 0,
        'total' => 2,
    ]);

    expect($report[1])->toBe([
        'student_id' => $students[1]->id,
        'student_name' => $students[1]->name,
        'present' => 0,
        'sick' => 1,
        'excused' => 0,
        'absent' => 1,
        'total' => 2,
    ]);
});

it('filters finalized attendance sessions by snapshot context', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $students = $data['students'];

    $otherTeacher = \App\Models\Teacher::create([
        'name' => 'Other Teacher',
        'email' => 'other.teacher@example.com',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $otherSubject = \App\Models\Subject::create([
        'name' => 'Other Subject',
        'code' => 'OTHER',
        'is_active' => true,
    ]);

    $otherLearningGroup = \App\Models\LearningGroup::create([
        'academic_period_id' => $schedule->academic_period_id,
        'name' => 'Other Group',
        'code' => 'OTHER',
        'description' => null,
        'is_active' => true,
    ]);

    $sessionOne = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-03',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    $sessionTwo = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $otherTeacher->id,
        'subject_id_snapshot' => $otherSubject->id,
        'learning_group_id_snapshot' => $otherLearningGroup->id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-04',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionOne->id,
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Present,
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionTwo->id,
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Sick,
    ]);

    $service = app(MonthlyAttendanceRecap::class);

    $byLearningGroup = $service->execute(
        Carbon::parse('2026-09-01'),
        learningGroupId: $schedule->learning_group_id,
    );

    $byTeacher = $service->execute(
        Carbon::parse('2026-09-01'),
        teacherId: $schedule->teacher_id,
    );

    $bySubject = $service->execute(
        Carbon::parse('2026-09-01'),
        subjectId: $schedule->subject_id,
    );

    expect($byLearningGroup)->toHaveCount(1);
    expect($byLearningGroup[0]['present'])->toBe(1);
    expect($byLearningGroup[0]['sick'])->toBe(0);

    expect($byTeacher)->toHaveCount(1);
    expect($byTeacher[0]['present'])->toBe(1);
    expect($byTeacher[0]['sick'])->toBe(0);

    expect($bySubject)->toHaveCount(1);
    expect($bySubject[0]['present'])->toBe(1);
    expect($bySubject[0]['sick'])->toBe(0);
});

it('excludes sessions outside the month and draft sessions', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $student = $data['students'][0];

    $this->actingAs($user);

    $createSession = function (string $date, AttendanceSessionStatus $status) use (
        $schedule,
        $user,
        $student,
    ) {
        $session = AttendanceSession::create([
            'schedule_id' => $schedule->id,
            'teacher_id_snapshot' => $schedule->teacher_id,
            'subject_id_snapshot' => $schedule->subject_id,
            'learning_group_id_snapshot' => $schedule->learning_group_id,
            'period_id_snapshot' => $schedule->period_id,
            'date' => $date,
            'status' => $status,
            'opened_at' => now(),
            'finalized_by' => $status === AttendanceSessionStatus::Finalized
                ? $user->id
                : null,
            'finalized_at' => $status === AttendanceSessionStatus::Finalized
                ? now()
                : null,
        ]);

        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present,
        ]);
    };

    $createSession('2026-08-31', AttendanceSessionStatus::Finalized);
    $createSession('2026-09-15', AttendanceSessionStatus::Draft);
    $createSession('2026-09-20', AttendanceSessionStatus::Finalized);
    $createSession('2026-10-01', AttendanceSessionStatus::Finalized);

    $report = app(MonthlyAttendanceRecap::class)->execute(
        Carbon::parse('2026-09-01'),
    );

    expect($report)->toHaveCount(1);

    expect($report[0])->toMatchArray([
        'student_id' => $student->id,
        'present' => 1,
        'sick' => 0,
        'excused' => 0,
        'absent' => 0,
        'total' => 1,
    ]);
});
