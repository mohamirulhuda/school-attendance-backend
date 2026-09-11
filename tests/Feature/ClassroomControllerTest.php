<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->activeClassroom = Classroom::create([
        'name' => 'A',
        'grade' => 12,
        'major' => 'IPA',
        'is_active' => true,
    ]);

    $this->inactiveClassroom = Classroom::create([
        'name' => 'B',
        'grade' => 12,
        'major' => 'IPS',
        'is_active' => false,
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

it('allows all application roles to list classrooms', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/classrooms')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $response = $this->actingAs($user)
            ->getJson('/api/classrooms')
            ->assertSuccessful();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.public_id'))
            ->toBe($this->activeClassroom->public_id);
    }
});

it('allows all application roles to view an active classroom by public id', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/classrooms/{$this->activeClassroom->public_id}")
            ->assertSuccessful()
            ->assertJsonPath(
                'data.public_id',
                $this->activeClassroom->public_id,
            )
            ->assertJsonPath('data.name', 'A')
            ->assertJsonPath('data.grade', 12)
            ->assertJsonPath('data.major', 'IPA')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');
    }
});

it('does not allow teachers to view an inactive classroom', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/classrooms/{$this->inactiveClassroom->public_id}")
            ->assertForbidden();
    }

    $this->actingAs($this->admin)
        ->getJson("/api/classrooms/{$this->inactiveClassroom->public_id}")
        ->assertSuccessful();
});

it('allows only admin to create classrooms', function () {
    $payload = [
        'grade' => 10,
        'name' => 'C',
        'major' => 'SAINS',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/classrooms', $payload)
        ->assertCreated()
        ->assertJsonPath('data.grade', 10)
        ->assertJsonPath('data.name', 'C')
        ->assertJsonPath('data.major', 'SAINS')
        ->assertJsonPath('data.is_active', true);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/classrooms', $payload)
            ->assertForbidden();
    }
});

it('allows only admin to update classrooms', function () {
    $payload = [
        'major' => 'KTI',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/classrooms/{$this->activeClassroom->public_id}",
            $payload,
        )
        ->assertSuccessful()
        ->assertJsonPath('data.major', 'KTI')
        ->assertJsonPath('data.is_active', false);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/classrooms/{$this->inactiveClassroom->public_id}",
                $payload,
            )
            ->assertForbidden();
    }
});

it('does not allow grade and name to be updated', function () {
    $this->actingAs($this->admin)
        ->patchJson(
            "/api/classrooms/{$this->activeClassroom->public_id}",
            [
                'grade' => 10,
                'name' => 'G',
            ],
        )
        ->assertSuccessful();

    $this->activeClassroom->refresh();

    expect($this->activeClassroom->grade)->toBe(12)
        ->and($this->activeClassroom->name)->toBe('A');
});

it('rejects invalid classroom input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/classrooms', [
            'grade' => 9,
            'name' => 'H',
            'major' => str_repeat('X', 51),
            'is_active' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'grade',
            'name',
            'major',
            'is_active',
        ]);
});

it('rejects duplicate grade and name combination', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/classrooms', [
            'grade' => 12,
            'name' => 'A',
            'major' => 'TAHFIDH',
            'is_active' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('allows major to be null', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/classrooms', [
            'grade' => 10,
            'name' => 'D',
            'major' => null,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.major', null);
});

it('returns classrooms ordered by grade and name ascending', function () {
    Classroom::create([
        'name' => 'A',
        'grade' => 10,
        'major' => null,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/classrooms')
        ->assertSuccessful();

    expect($response->json('data.0.grade'))->toBe(10)
        ->and($response->json('data.0.name'))->toBe('A')
        ->and($response->json('data.1.grade'))->toBe(12)
        ->and($response->json('data.1.name'))->toBe('A');
});

it('allows admin to filter classrooms by active state', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/classrooms?is_active=true')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeClassroom->public_id);

    $this->actingAs($this->admin)
        ->getJson('/api/classrooms?is_active=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveClassroom->public_id);
});

it('does not let teachers bypass the active classroom scope with the filter', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/classrooms?is_active=false')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.public_id',
                $this->activeClassroom->public_id,
            );
    }
});

it('does not allow users without an application role to access classrooms', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/api/classrooms')
        ->assertForbidden();
});

it('does not expose deleted classrooms through the API', function () {
    $this->activeClassroom->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/classrooms')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.public_id',
            $this->inactiveClassroom->public_id,
        );

    $this->actingAs($this->admin)
        ->getJson("/api/classrooms/{$this->activeClassroom->public_id}")
        ->assertNotFound();
});

it('does not accept internal classroom id as the public route identifier', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/classrooms/{$this->activeClassroom->id}")
        ->assertNotFound();
});

it('does not expose delete as an API operation', function () {
    $this->actingAs($this->admin)
        ->deleteJson("/api/classrooms/{$this->activeClassroom->public_id}")
        ->assertMethodNotAllowed();
});
