<?php

use App\Enums\Semester;
use App\Http\Requests\StoreAcademicPeriodRequest;
use App\Http\Requests\UpdateAcademicPeriodRequest;
use App\Models\AcademicPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('accepts valid store academic period data', function () {
    $request = StoreAcademicPeriodRequest::create(
        '/api/academic-periods',
        'POST',
        [
            'academic_year' => '2026/2027',
            'semester' => Semester::Odd->value,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ],
    );

    $request->setUserResolver(fn () => $this->admin);

    $validator = Validator::make(
        $request->all(),
        $request->rules(),
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects invalid semester', function () {
    $request = StoreAcademicPeriodRequest::create(
        '/api/academic-periods',
        'POST',
        [
            'academic_year' => '2026/2027',
            'semester' => 'Invalid',
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ],
    );

    $validator = Validator::make(
        $request->all(),
        $request->rules(),
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects invalid academic year format', function () {
    $request = StoreAcademicPeriodRequest::create(
        '/api/academic-periods',
        'POST',
        [
            'academic_year' => '2026',
            'semester' => Semester::Odd->value,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ],
    );

    $validator = Validator::make(
        $request->all(),
        $request->rules(),
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects invalid date values', function () {
    $request = StoreAcademicPeriodRequest::create(
        '/api/academic-periods',
        'POST',
        [
            'academic_year' => '2026/2027',
            'semester' => Semester::Odd->value,
            'starts_at' => 'invalid-date',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ],
    );

    $validator = Validator::make(
        $request->all(),
        $request->rules(),
    );

    expect($validator->fails())->toBeTrue();
});

it('accepts valid update academic period data', function () {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $request = UpdateAcademicPeriodRequest::create(
        "/api/academic-periods/{$academicPeriod->public_id}",
        'PATCH',
        [
            'starts_at' => '2026-07-02',
            'ends_at' => '2026-12-30',
            'is_active' => false,
        ],
    );

    $request->setUserResolver(fn () => $this->admin);

    $validator = Validator::make(
        $request->all(),
        $request->rules(),
    );

    expect($validator->passes())->toBeTrue();
});

it('does not allow immutable academic period fields during update', function () {
    $request = UpdateAcademicPeriodRequest::create(
        '/api/academic-periods/test',
        'PATCH',
        [
            'academic_year' => '2027/2028',
            'semester' => Semester::Even->value,
            'starts_at' => '2026-07-02',
            'ends_at' => '2026-12-30',
            'is_active' => false,
        ],
    );

    $rules = $request->rules();

    expect($rules)
        ->not->toHaveKey('academic_year')
        ->not->toHaveKey('semester');
});
