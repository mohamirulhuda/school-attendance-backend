<?php

use App\Enums\Semester;
use App\Http\Resources\AcademicPeriodResource;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the expected academic period response fields', function () {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $resource = new AcademicPeriodResource($academicPeriod);

    $response = $resource->toArray(Request::create('/api/academic-periods', 'GET'));

    expect($response['public_id'])->toBe($academicPeriod->public_id);
    expect($response['academic_year'])->toBe('2026/2027');
    expect($response['semester'])->toBe(Semester::Odd);
    expect($response['starts_at'])->toBe('2026-07-01');
    expect($response['ends_at'])->toBe('2026-12-31');
    expect($response['is_active'])->toBeTrue();
});

it('does not expose internal or timestamp fields', function () {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $resource = new AcademicPeriodResource($academicPeriod);

    $response = $resource->toArray(Request::create('/api/academic-periods', 'GET'));

    expect($response)
        ->not->toHaveKey('id')
        ->not->toHaveKey('created_at')
        ->not->toHaveKey('updated_at');
});
