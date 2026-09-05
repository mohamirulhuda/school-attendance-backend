<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\AttendanceSession;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Policies\AttendanceSessionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = app(AttendanceSessionPolicy::class);

    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->ownerTeacher = Teacher::create([
        'name' => 'Owner Teacher',
        'email' => 'owner.teacher@example.com',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $this->otherTeacher = Teacher::create([
        'name' => 'Other Teacher',
        'email' => 'other.teacher@example.com',
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
        'semester' => Semester::Odd,
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

    $this->schedule = Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->learningGroup->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->ownerTeacher->id,
        'period_id' => $this->period->id,
        'day_of_week' => 1,
    ]);

    $this->session = AttendanceSession::create([
        'schedule_id' => $this->schedule->id,
        'teacher_id_snapshot' => $this->ownerTeacher->id,
        'subject_id_snapshot' => $this->subject->id,
        'learning_group_id_snapshot' => $this->learningGroup->id,
        'period_id_snapshot' => $this->period->id,
        'date' => '2026-07-01',
        'status' => AttendanceSessionStatus::Draft,
        'opened_at' => now(),
    ]);

    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
    ]);
    $this->admin->assignRole('admin');

    $this->piket = User::factory()->create([
        'email' => 'piket@example.com',
    ]);
    $this->piket->assignRole('guru_piket');

    $this->ownerTeacherUser = User::factory()->create([
        'email' => $this->ownerTeacher->email,
    ]);
    $this->ownerTeacherUser->assignRole('guru_mapel');

    $this->otherTeacherUser = User::factory()->create([
        'email' => $this->otherTeacher->email,
    ]);
    $this->otherTeacherUser->assignRole('guru_mapel');
});

it('allows admin to view, update, and finalize any attendance session', function () {
    expect($this->policy->view($this->admin, $this->session))->toBeTrue();
    expect($this->policy->update($this->admin, $this->session))->toBeTrue();
    expect($this->policy->finalize($this->admin, $this->session))->toBeTrue();
});

it('allows guru piket to view, update, and finalize any attendance session', function () {
    expect($this->policy->view($this->piket, $this->session))->toBeTrue();
    expect($this->policy->update($this->piket, $this->session))->toBeTrue();
    expect($this->policy->finalize($this->piket, $this->session))->toBeTrue();
});

it('allows guru mapel to view, update, and finalize their own attendance session', function () {
    expect($this->policy->view($this->ownerTeacherUser, $this->session))->toBeTrue();
    expect($this->policy->update($this->ownerTeacherUser, $this->session))->toBeTrue();
    expect($this->policy->finalize($this->ownerTeacherUser, $this->session))->toBeTrue();
});

it('forbids guru mapel from another teachers attendance session', function () {
    expect($this->policy->view($this->otherTeacherUser, $this->session))->toBeFalse();
    expect($this->policy->update($this->otherTeacherUser, $this->session))->toBeFalse();
    expect($this->policy->finalize($this->otherTeacherUser, $this->session))->toBeFalse();
});
