<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AcademicPeriod;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\LearningGroup;
use App\Models\LearningGroupStudent;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Attendance\StartAttendanceSession;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->teacher = Teacher::create([
        'name' => 'Test Teacher',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $this->subject = Subject::create([
        'code' => 'TEST',
        'name' => 'Test Subject',
        'is_active' => true,
    ]);

    $this->academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => \App\Enums\Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->learningGroup = LearningGroup::create([
        'academic_period_id' => $this->academicPeriod->id,
        'name' => 'Test Class',
        'code' => 'TEST',
        'is_active' => true,
    ]);

    $this->period = Period::create([
        'number' => 99,
        'name' => 'Test Period',
        'starts_at' => '07:00',
        'ends_at' => '08:00',
        'is_active' => true,
    ]);

    $students = collect([
        Student::create([
            'name' => 'Test Student 1',
            'gender' => 'L',
            'is_active' => true,
        ]),
        Student::create([
            'name' => 'Test Student 2',
            'gender' => 'P',
            'is_active' => true,
        ]),
    ]);

    foreach ($students as $student) {
        LearningGroupStudent::create([
            'learning_group_id' => $this->learningGroup->id,
            'student_id' => $student->id,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
        ]);
    }

    $this->schedule = Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->learningGroup->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'period_id' => $this->period->id,
        'day_of_week' => 1,
    ]);

    $this->actingAs($this->user);
});

it('creates an attendance session with eligible students', function () {
    $session = app(StartAttendanceSession::class)->execute(
        $this->schedule->id,
        Carbon::parse('2026-07-01'),
    );

    expect($session)
        ->toBeInstanceOf(AttendanceSession::class);

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Draft);

    expect($session->schedule_id)
        ->toBe($this->schedule->id);

    expect($session->teacher_id_snapshot)
        ->toBe($this->teacher->id);

    expect($session->subject_id_snapshot)
        ->toBe($this->subject->id);

    expect($session->learning_group_id_snapshot)
        ->toBe($this->learningGroup->id);

    expect($session->period_id_snapshot)
        ->toBe($this->period->id);

    expect($session->attendanceRecords()->count())
        ->toBe(2);

    expect($session->attendanceRecords()->pluck('status')->all())
        ->each->toBe(AttendanceStatus::Present);

    expect(
        AuditLog::query()
            ->where('auditable_type', AttendanceSession::class)
            ->where('auditable_id', $session->id)
            ->count()
    )->toBe(1);
});

it('does not duplicate an existing attendance session', function () {
    $service = app(StartAttendanceSession::class);

    $firstSession = $service->execute(
        $this->schedule->id,
        Carbon::parse('2026-07-01'),
    );

    $secondSession = $service->execute(
        $this->schedule->id,
        Carbon::parse('2026-07-01'),
    );

    expect($secondSession->id)
        ->toBe($firstSession->id);

    expect(AttendanceSession::count())
        ->toBe(1);

    expect(
        AttendanceRecord::where(
            'attendance_session_id',
            $firstSession->id
        )->count()
    )->toBe(2);

    expect(
        AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'create')
            ->count()
    )->toBe(1);
});

it('rejects attendance when there are no eligible students', function () {
    $this->learningGroup->memberships()->delete();

    expect(fn () => app(StartAttendanceSession::class)->execute(
        $this->schedule->id,
        Carbon::parse('2026-07-01'),
    ))->toThrow(ValidationException::class);

    expect(AttendanceSession::count())
        ->toBe(0);

    expect(AttendanceRecord::count())
        ->toBe(0);

    expect(AuditLog::query()
        ->where('domain', 'attendance')
        ->count()
    )->toBe(0);
});
