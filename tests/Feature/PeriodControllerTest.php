<?php

use App\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->period = Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
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

it('allows all application roles to list periods', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/periods')
            ->assertSuccessful()
            ->assertJsonPath('data.0.public_id', $this->period->public_id)
            ->assertJsonPath('data.0.number', 1)
            ->assertJsonPath('data.0.name', 'Jam 1')
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.created_at')
            ->assertJsonMissingPath('data.0.updated_at');
    }
});

it('allows all application roles to view a period by public id', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/periods/{$this->period->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $this->period->public_id)
            ->assertJsonPath('data.number', 1)
            ->assertJsonPath('data.name', 'Jam 1')
            ->assertJsonPath('data.starts_at', '07:00')
            ->assertJsonPath('data.ends_at', '07:45')
            ->assertJsonPath('data.is_active', true);
    }
});

it('allows only admin to create periods', function () {
    $payload = [
        'number' => 2,
        'name' => 'Jam 2',
        'starts_at' => '07:45',
        'ends_at' => '08:30',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/periods', $payload)
        ->assertCreated()
        ->assertJsonPath('data.number', 2)
        ->assertJsonPath('data.name', 'Jam 2')
        ->assertJsonPath('data.starts_at', '07:45')
        ->assertJsonPath('data.ends_at', '08:30')
        ->assertJsonPath('data.is_active', true);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/periods', $payload)
            ->assertForbidden();
    }
});

it('allows only admin to update periods', function () {
    $payload = [
        'name' => 'Jam Pertama',
        'starts_at' => '07:15',
        'ends_at' => '08:00',
    ];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/periods/{$this->period->public_id}",
            $payload
        )
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Jam Pertama')
        ->assertJsonPath('data.starts_at', '07:15')
        ->assertJsonPath('data.ends_at', '08:00');

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/periods/{$this->period->public_id}",
                $payload
            )
            ->assertForbidden();
    }
});

it('rejects invalid period input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/periods', [
            'number' => 0,
            'name' => '',
            'starts_at' => '08:00',
            'ends_at' => '07:00',
            'is_active' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'number',
            'name',
            'ends_at',
        ]);
});

it('returns periods ordered by number ascending', function () {
    Period::create([
        'number' => 2,
        'name' => 'Jam 2',
        'starts_at' => '07:45',
        'ends_at' => '08:30',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/periods')
        ->assertSuccessful();

    expect($response->json('data.0.number'))->toBe(1)
        ->and($response->json('data.1.number'))->toBe(2);
});

it('does not allow users without an application role to access periods', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/api/periods')
        ->assertForbidden();
});

it('returns not found when using internal period id instead of public id', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/periods/{$this->period->id}")
        ->assertNotFound();
});
