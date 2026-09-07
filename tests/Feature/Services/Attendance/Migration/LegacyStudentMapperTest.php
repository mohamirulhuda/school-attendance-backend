<?php

use App\Enums\Gender;
use App\Models\Student;
use App\Services\Attendance\Migration\LegacyStudentMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('maps student by exact normalized name', function (): void {
    $student = Student::create([
        'name' => 'Abdul Ghani Shalihin',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyStudentMapper::class)->map(
        'Abdul Ghani Shalihin'
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->status)->toBe('match')
        ->and($result->student?->id)->toBe($student->id);
});

it('normalizes student name whitespace and case', function (): void {
    $student = Student::create([
        'name' => 'Abdul Ghani Shalihin',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyStudentMapper::class)->map(
        '  ABDUL   GHANI   SHALIHIN  '
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->student?->id)->toBe($student->id);
});

it('returns not found for unknown student', function (): void {
    $result = app(LegacyStudentMapper::class)->map(
        'Siswa Tidak Ada'
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('not_found')
        ->and($result->student)->toBeNull();
});

it('returns ambiguous for multiple matching students', function (): void {
    Student::create([
        'name' => 'Student Sama',
        'gender' => Gender::Male,
    ]);

    Student::create([
        'name' => 'Student Sama',
        'gender' => Gender::Female,
    ]);

    $result = app(LegacyStudentMapper::class)->map(
        'Student Sama'
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('ambiguous')
        ->and($result->student)->toBeNull();
});

it('does not use nis as identity', function (): void {
    $student = Student::create([
        'nis' => '999999',
        'name' => 'Abdul Ghani Shalihin',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyStudentMapper::class)->map(
        'Abdul Ghani Shalihin'
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->student?->id)->toBe($student->id);
});
