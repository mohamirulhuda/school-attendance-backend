<?php

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\User;
use App\Policies\AcademicPeriodPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = app(AcademicPeriodPolicy::class);

    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
    ]);
    $this->admin->assignRole('admin');

    $this->piket = User::factory()->create([
        'email' => 'piket@example.com',
    ]);
    $this->piket->assignRole('guru_piket');

    $this->guruMapel = User::factory()->create([
        'email' => 'guru.mapel@example.com',
    ]);
    $this->guruMapel->assignRole('guru_mapel');
});

it('allows all application roles to view academic periods', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue();
    expect($this->policy->view($this->admin, $this->academicPeriod))->toBeTrue();

    expect($this->policy->viewAny($this->piket))->toBeTrue();
    expect($this->policy->view($this->piket, $this->academicPeriod))->toBeTrue();

    expect($this->policy->viewAny($this->guruMapel))->toBeTrue();
    expect($this->policy->view($this->guruMapel, $this->academicPeriod))->toBeTrue();
});

it('allows only admin to create academic periods', function () {
    expect($this->policy->create($this->admin))->toBeTrue();
    expect($this->policy->create($this->piket))->toBeFalse();
    expect($this->policy->create($this->guruMapel))->toBeFalse();
});

it('allows only admin to update academic periods', function () {
    expect($this->policy->update($this->admin, $this->academicPeriod))->toBeTrue();
    expect($this->policy->update($this->piket, $this->academicPeriod))->toBeFalse();
    expect($this->policy->update($this->guruMapel, $this->academicPeriod))->toBeFalse();
});

it('forbids deleting academic periods', function () {
    expect($this->policy->delete($this->admin, $this->academicPeriod))->toBeFalse();
    expect($this->policy->delete($this->piket, $this->academicPeriod))->toBeFalse();
    expect($this->policy->delete($this->guruMapel, $this->academicPeriod))->toBeFalse();
});

it('forbids restoring and force deleting academic periods', function () {
    expect($this->policy->restore($this->admin, $this->academicPeriod))->toBeFalse();
    expect($this->policy->forceDelete($this->admin, $this->academicPeriod))->toBeFalse();
});
