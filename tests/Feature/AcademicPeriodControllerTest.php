<?php

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
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

it('allows all application roles to list academic periods', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/academic-periods')
            ->assertSuccessful()
            ->assertJsonPath('data.0.public_id', $this->academicPeriod->public_id)
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.created_at')
            ->assertJsonMissingPath('data.0.updated_at');
    }
});

it('allows all application roles to view an academic period by public id', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/academic-periods/{$this->academicPeriod->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $this->academicPeriod->public_id)
            ->assertJsonPath('data.academic_year', '2026/2027')
            ->assertJsonPath('data.semester', 'Ganjil');
    }
});

it('allows only admin to create academic periods', function () {
    $payload = [
        'academic_year' => '2027/2028',
        'semester' => 'Genap',
        'starts_at' => '2028-01-01',
        'ends_at' => '2028-06-30',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/academic-periods', $payload)
        ->assertCreated()
        ->assertJsonPath('data.academic_year', '2027/2028')
        ->assertJsonPath('data.semester', 'Genap');

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/academic-periods', $payload)
            ->assertForbidden();
    }
});

it('allows only admin to update an academic period', function () {
    $payload = [
        'starts_at' => '2026-07-15',
        'ends_at' => '2026-12-20',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/academic-periods/{$this->academicPeriod->public_id}",
            $payload
        )
        ->assertSuccessful()
        ->assertJsonPath('data.starts_at', '2026-07-15')
        ->assertJsonPath('data.ends_at', '2026-12-20')
        ->assertJsonPath('data.is_active', false);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/academic-periods/{$this->academicPeriod->public_id}",
                $payload
            )
            ->assertForbidden();
    }
});

it('does not allow immutable academic period fields to be updated', function () {
    $this->actingAs($this->admin)
        ->patchJson(
            "/api/academic-periods/{$this->academicPeriod->public_id}",
            [
                'academic_year' => '2027/2028',
                'semester' => 'Genap',
            ]
        )
        ->assertSuccessful();

    $this->academicPeriod->refresh();

    expect($this->academicPeriod->academic_year)->toBe('2026/2027')
        ->and($this->academicPeriod->semester)->toBe(Semester::Odd);
});

it('returns not found when using internal academic period id instead of public id', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/academic-periods/{$this->academicPeriod->id}")
        ->assertNotFound();
});
