<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Services\Attendance\Reports\DailyAttendanceRecap;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('recaps finalized attendance sessions for a specific date', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $students = $data['students'];
    $period = $data['period'];

    $periodTwo = \App\Models\Period::create([
        'number' => 100,
        'name' => 'Test Period 2',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    $scheduleTwo = \App\Models\Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $schedule->learning_group_id,
        'subject_id' => $schedule->subject_id,
        'teacher_id' => $schedule->teacher_id,
        'period_id' => $periodTwo->id,
        'day_of_week' => 1,
    ]);

    $sessionOne = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-10',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    $sessionTwo = AttendanceSession::create([
        'schedule_id' => $scheduleTwo->id,
        'teacher_id_snapshot' => $scheduleTwo->teacher_id,
        'subject_id_snapshot' => $scheduleTwo->subject_id,
        'learning_group_id_snapshot' => $scheduleTwo->learning_group_id,
        'period_id_snapshot' => $scheduleTwo->period_id,
        'date' => '2026-09-10',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionOne->id,
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Present,
        'note' => null,
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionOne->id,
        'student_id' => $students[1]->id,
        'status' => AttendanceStatus::Sick,
        'note' => 'Demam',
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionTwo->id,
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Excused,
        'note' => 'Keperluan keluarga',
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $sessionTwo->id,
        'student_id' => $students[1]->id,
        'status' => AttendanceStatus::Absent,
        'note' => null,
    ]);

    $report = app(DailyAttendanceRecap::class)->execute(
        Carbon::parse('2026-09-10'),
    );

    expect($report)->toHaveCount(2);

    expect($report[0])->toMatchArray([
        'session_id' => $sessionOne->id,
        'date' => '2026-09-10',
        'teacher_id' => $schedule->teacher_id,
        'subject_id' => $schedule->subject_id,
        'learning_group_id' => $schedule->learning_group_id,
        'period_id' => $period->id,
    ]);

    expect($report[0]['period'])->toMatchArray([
        'number' => $period->number,
        'name' => $period->name,
        'starts_at' => $period->starts_at,
        'ends_at' => $period->ends_at,
    ]);

    expect($report[0]['students'])->toBe([
        [
            'student_id' => $students[0]->id,
            'student_name' => $students[0]->name,
            'status' => 'present',
            'note' => null,
        ],
        [
            'student_id' => $students[1]->id,
            'student_name' => $students[1]->name,
            'status' => 'sick',
            'note' => 'Demam',
        ],
    ]);

    expect($report[1])->toMatchArray([
        'session_id' => $sessionTwo->id,
        'date' => '2026-09-10',
        'teacher_id' => $scheduleTwo->teacher_id,
        'subject_id' => $scheduleTwo->subject_id,
        'learning_group_id' => $scheduleTwo->learning_group_id,
        'period_id' => $periodTwo->id,
    ]);

    expect($report[1]['period'])->toMatchArray([
        'number' => $periodTwo->number,
        'name' => $periodTwo->name,
        'starts_at' => $periodTwo->starts_at,
        'ends_at' => $periodTwo->ends_at,
    ]);

    expect($report[1]['students'])->toBe([
        [
            'student_id' => $students[0]->id,
            'student_name' => $students[0]->name,
            'status' => 'excused',
            'note' => 'Keperluan keluarga',
        ],
        [
            'student_id' => $students[1]->id,
            'student_name' => $students[1]->name,
            'status' => 'absent',
            'note' => null,
        ],
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

    $periodTwo = \App\Models\Period::create([
        'number' => 100,
        'name' => 'Test Period 2',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
        'is_active' => true,
    ]);

    $scheduleTwo = \App\Models\Schedule::create([
        'academic_period_id' => $schedule->academic_period_id,
        'learning_group_id' => $otherLearningGroup->id,
        'subject_id' => $otherSubject->id,
        'teacher_id' => $otherTeacher->id,
        'period_id' => $periodTwo->id,
        'day_of_week' => 1,
    ]);

    $sessionOne = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-10',
        'status' => AttendanceSessionStatus::Finalized,
        'opened_at' => now(),
        'finalized_by' => $user->id,
        'finalized_at' => now(),
    ]);

    $sessionTwo = AttendanceSession::create([
        'schedule_id' => $scheduleTwo->id,
        'teacher_id_snapshot' => $otherTeacher->id,
        'subject_id_snapshot' => $otherSubject->id,
        'learning_group_id_snapshot' => $otherLearningGroup->id,
        'period_id_snapshot' => $periodTwo->id,
        'date' => '2026-09-10',
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

    $service = app(DailyAttendanceRecap::class);

    $byLearningGroup = $service->execute(
        Carbon::parse('2026-09-10'),
        learningGroupId: $schedule->learning_group_id,
    );

    $byTeacher = $service->execute(
        Carbon::parse('2026-09-10'),
        teacherId: $schedule->teacher_id,
    );

    $bySubject = $service->execute(
        Carbon::parse('2026-09-10'),
        subjectId: $schedule->subject_id,
    );

    expect($byLearningGroup)->toHaveCount(1);
    expect($byLearningGroup[0]['session_id'])->toBe($sessionOne->id);
    expect($byLearningGroup[0]['students'][0]['status'])->toBe('present');

    expect($byTeacher)->toHaveCount(1);
    expect($byTeacher[0]['session_id'])->toBe($sessionOne->id);
    expect($byTeacher[0]['students'][0]['status'])->toBe('present');

    expect($bySubject)->toHaveCount(1);
    expect($bySubject[0]['session_id'])->toBe($sessionOne->id);
    expect($bySubject[0]['students'][0]['status'])->toBe('present');
});

it('excludes draft sessions from the daily recap', function () {
    $data = createAttendanceTestData();

    $user = $data['user'];
    $schedule = $data['schedule'];
    $student = $data['students'][0];

    $this->actingAs($user);

    $draftSession = AttendanceSession::create([
        'schedule_id' => $schedule->id,
        'teacher_id_snapshot' => $schedule->teacher_id,
        'subject_id_snapshot' => $schedule->subject_id,
        'learning_group_id_snapshot' => $schedule->learning_group_id,
        'period_id_snapshot' => $schedule->period_id,
        'date' => '2026-09-10',
        'status' => AttendanceSessionStatus::Draft,
        'opened_at' => now(),
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $draftSession->id,
        'student_id' => $student->id,
        'status' => AttendanceStatus::Present,
    ]);

    $report = app(DailyAttendanceRecap::class)->execute(
        Carbon::parse('2026-09-10'),
    );

    expect($report)->toBeEmpty();
});

