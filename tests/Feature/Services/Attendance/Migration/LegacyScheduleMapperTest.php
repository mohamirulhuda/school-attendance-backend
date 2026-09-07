<?php

use App\Enums\Semester;
use App\Enums\Gender;
use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Attendance\Migration\LegacyScheduleMapper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('maps a schedule by learning group teacher period and iso weekday', function (): void {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $learningGroup = LearningGroup::create([
        'academic_period_id' => $academicPeriod->id,
        'name' => 'X C',
        'code' => 'X-C',
        'is_active' => true,
    ]);

    $teacher = Teacher::create([
        'name' => 'Teacher Test',
        'gender' => Gender::Male,
    ]);

    $period = Period::create([
        'number' => 1,
        'name' => '1 - 2',
        'starts_at' => '07:00',
        'ends_at' => '08:30',
        'is_active' => true,
    ]);

    $subject = Subject::create([
        'code' => 'TEST',
        'name' => 'Subject Test',
        'is_active' => true,
    ]);

    $schedule = Schedule::create([
        'academic_period_id' => $academicPeriod->id,
        'learning_group_id' => $learningGroup->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'period_id' => $period->id,
        'day_of_week' => 4,
    ]);

    $result = app(LegacyScheduleMapper::class)->map(
        learningGroupId: $learningGroup->id,
        teacherId: $teacher->id,
        date: Carbon::parse('2026-07-23'),
        periodId: $period->id,
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->status)->toBe('match')
        ->and($result->schedule?->id)->toBe($schedule->id);
});

it('uses iso weekday instead of carbon zero based weekday', function (): void {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $learningGroup = LearningGroup::create([
        'academic_period_id' => $academicPeriod->id,
        'name' => 'X C',
        'code' => 'X-C',
        'is_active' => true,
    ]);

    $teacher = Teacher::create([
        'name' => 'Teacher Test',
        'gender' => Gender::Male,
    ]);

    $period = Period::create([
        'number' => 2,
        'name' => '3 - 4',
        'starts_at' => '08:30',
        'ends_at' => '10:00',
        'is_active' => true,
    ]);

    $subject = Subject::create([
        'code' => 'TEST',
        'name' => 'Subject Test',
        'is_active' => true,
    ]);

    $schedule = Schedule::create([
        'academic_period_id' => $academicPeriod->id,
        'learning_group_id' => $learningGroup->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'period_id' => $period->id,
        'day_of_week' => 1,
    ]);

    $result = app(LegacyScheduleMapper::class)->map(
        learningGroupId: $learningGroup->id,
        teacherId: $teacher->id,
        date: Carbon::parse('2026-07-20'),
        periodId: $period->id,
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->schedule?->id)->toBe($schedule->id);
});

it('returns not found when no schedule matches the legacy context', function (): void {
    $result = app(LegacyScheduleMapper::class)->map(
        learningGroupId: 1,
        teacherId: 1,
        date: Carbon::parse('2026-07-23'),
        periodId: 1,
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('not_found')
        ->and($result->schedule)->toBeNull();
});

it('does not match when any part of the legacy schedule context differs', function (): void {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $learningGroup = LearningGroup::create([
        'academic_period_id' => $academicPeriod->id,
        'name' => 'X C',
        'code' => 'X-C',
        'is_active' => true,
    ]);

    $teacher = Teacher::create([
        'name' => 'Teacher Test',
        'gender' => Gender::Male,
    ]);

    $otherTeacher = Teacher::create([
        'name' => 'Other Teacher',
        'gender' => Gender::Male,
    ]);

    $period = Period::create([
        'number' => 2,
        'name' => '3 - 4',
        'starts_at' => '08:30',
        'ends_at' => '10:00',
        'is_active' => true,
    ]);

    $otherPeriod = Period::create([
        'number' => 3,
        'name' => '5 - 6',
        'starts_at' => '10:00',
        'ends_at' => '11:30',
        'is_active' => true,
    ]);

    $subject = Subject::create([
        'code' => 'TEST',
        'name' => 'Subject Test',
        'is_active' => true,
    ]);

    Schedule::create([
        'academic_period_id' => $academicPeriod->id,
        'learning_group_id' => $learningGroup->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'period_id' => $period->id,
        'day_of_week' => 4,
    ]);

    $result = app(LegacyScheduleMapper::class)->map(
        learningGroupId: $learningGroup->id,
        teacherId: $otherTeacher->id,
        date: Carbon::parse('2026-07-23'),
        periodId: $otherPeriod->id,
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('not_found')
        ->and($result->schedule)->toBeNull();
});
