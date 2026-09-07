<?php

use App\Enums\Gender;
use App\Models\Teacher;
use App\Services\Attendance\Migration\LegacyTeacherMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('maps teacher with prefix and suffix', function (): void {
    $teacher = Teacher::create([
        'title_prefix' => 'Dr',
        'name' => 'Mustajib',
        'title_suffix' => 'M.Pd.I.',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyTeacherMapper::class)->map(
        'Dr. Mustajib, M.Pd.I.'
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->status)->toBe('match')
        ->and($result->teacher?->id)->toBe($teacher->id);
});

it('normalizes prefix format without removing the prefix', function (): void {
    $teacher = Teacher::create([
        'title_prefix' => 'Dr',
        'name' => 'Mustajib',
        'title_suffix' => 'M.Pd.I.',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyTeacherMapper::class)->map(
        'Dr Mustajib, M.Pd.I.'
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->teacher?->id)->toBe($teacher->id);
});

it('maps teacher without prefix', function (): void {
    $teacher = Teacher::create([
        'title_prefix' => null,
        'name' => 'Ahmad Burhanudin Nuron A\'la',
        'title_suffix' => 'S.Sos.',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyTeacherMapper::class)->map(
        'Ahmad Burhanudin Nuron A\'la, S.Sos.'
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->teacher?->id)->toBe($teacher->id);
});

it('does not ignore a missing prefix', function (): void {
    Teacher::create([
        'title_prefix' => 'Dr',
        'name' => 'Mustajib',
        'title_suffix' => 'M.Pd.I.',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyTeacherMapper::class)->map(
        'Mustajib, M.Pd.I.'
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('not_found')
        ->and($result->teacher)->toBeNull();
});

it('returns not found for unknown teacher', function (): void {
    $result = app(LegacyTeacherMapper::class)->map(
        'Teacher Tidak Ada, S.Pd.'
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('not_found')
        ->and($result->teacher)->toBeNull();
});

it('returns ambiguous for multiple matching teachers', function (): void {
    Teacher::create([
        'title_prefix' => null,
        'name' => 'Teacher Sama',
        'title_suffix' => 'S.Pd.',
        'gender' => Gender::Male,
    ]);

    Teacher::create([
        'title_prefix' => null,
        'name' => 'Teacher Sama',
        'title_suffix' => 'S.Pd.',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyTeacherMapper::class)->map(
        'Teacher Sama, S.Pd.'
    );

    expect($result->isMatched())->toBeFalse()
        ->and($result->status)->toBe('ambiguous')
        ->and($result->teacher)->toBeNull();
});

it('does not use email as identity', function (): void {
    $teacher = Teacher::create([
        'name' => 'Moh. Amirul Huda',
        'title_prefix' => null,
        'title_suffix' => null,
        'email' => 'master@example.com',
        'gender' => Gender::Male,
    ]);

    $result = app(LegacyTeacherMapper::class)->map(
        'Moh. Amirul Huda'
    );

    expect($result->isMatched())->toBeTrue()
        ->and($result->teacher?->id)->toBe($teacher->id);
});
