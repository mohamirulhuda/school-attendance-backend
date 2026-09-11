<?php

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->activeSubject = Subject::create([
        'code' => 'MAT',
        'name' => 'Matematika',
        'is_active' => true,
    ]);

    $this->inactiveSubject = Subject::create([
        'code' => 'SEJ',
        'name' => 'Sejarah',
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

it('allows all application roles to list subjects', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/subjects')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/subjects')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $this->activeSubject->public_id);
    }
});

it('allows all application roles to view an active subject', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/subjects/{$this->activeSubject->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $this->activeSubject->public_id)
            ->assertJsonPath('data.code', 'MAT')
            ->assertJsonPath('data.name', 'Matematika')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at')
            ->assertJsonMissingPath('data.schedules');
    }
});

it('allows admin to view an inactive subject', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/subjects/{$this->inactiveSubject->public_id}")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);
});

it('does not allow teachers to view an inactive subject', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/subjects/{$this->inactiveSubject->public_id}")
            ->assertForbidden();
    }
});

it('allows only admin to create subjects', function () {
    $payload = [
        'code' => 'BIO',
        'name' => 'Biologi',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/subjects', $payload)
        ->assertSuccessful()
        ->assertJsonPath('data.code', 'BIO')
        ->assertJsonPath('data.name', 'Biologi')
        ->assertJsonPath('data.is_active', true);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/subjects', $payload)
            ->assertForbidden();
    }
});

it('allows only admin to update subjects', function () {
    $payload = [
        'code' => 'MAT-1',
        'name' => 'Matematika Wajib',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/subjects/{$this->activeSubject->public_id}",
            $payload,
        )
        ->assertSuccessful()
        ->assertJsonPath('data.code', 'MAT-1')
        ->assertJsonPath('data.name', 'Matematika Wajib')
        ->assertJsonPath('data.is_active', false);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/subjects/{$this->activeSubject->public_id}",
                $payload,
            )
            ->assertForbidden();
    }
});

it('allows only admin to soft delete subjects', function () {
    $this->actingAs($this->admin)
        ->deleteJson("/api/subjects/{$this->activeSubject->public_id}")
        ->assertNoContent();

    expect(Subject::withTrashed()->find($this->activeSubject->id)->deleted_at)
        ->not->toBeNull();

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->deleteJson("/api/subjects/{$this->inactiveSubject->public_id}")
            ->assertForbidden();
    }
});

it('rejects invalid subject input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/subjects', [
            'code' => '',
            'name' => '',
            'is_active' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
            'name',
            'is_active',
        ]);
});

it('rejects a missing subject code', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/subjects', [
            'name' => 'Fisika',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('rejects duplicate subject codes globally', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/subjects', [
            'code' => $this->activeSubject->code,
            'name' => 'Matematika Lain',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('does not allow a soft-deleted subject code to be reused', function () {
    $this->activeSubject->delete();

    $this->actingAs($this->admin)
        ->postJson('/api/subjects', [
            'code' => 'MAT',
            'name' => 'Matematika Baru',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('allows updating a subject without changing its code', function () {
    $this->actingAs($this->admin)
        ->patchJson(
            "/api/subjects/{$this->activeSubject->public_id}",
            ['name' => 'Matematika Dasar'],
        )
        ->assertSuccessful()
        ->assertJsonPath('data.code', 'MAT')
        ->assertJsonPath('data.name', 'Matematika Dasar');
});

it('rejects changing a subject code to another existing code', function () {
    $this->actingAs($this->admin)
        ->patchJson(
            "/api/subjects/{$this->activeSubject->public_id}",
            ['code' => 'SEJ'],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('filters subjects by active state for admin', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/subjects?is_active=true')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeSubject->public_id);

    $this->actingAs($this->admin)
        ->getJson('/api/subjects?is_active=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveSubject->public_id);
});

it('does not let teachers bypass the active scope with the is_active filter', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/subjects?is_active=false')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $this->activeSubject->public_id);
    }
});

it('filters subjects by name and code', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/subjects?name=matem')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'MAT');

    $this->actingAs($this->admin)
        ->getJson('/api/subjects?code=SEJ')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Sejarah');
});

it('returns subjects ordered by id ascending', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/subjects')
        ->assertSuccessful();

    expect($response->json('data.0.public_id'))
        ->toBe($this->activeSubject->public_id)
        ->and($response->json('data.1.public_id'))
        ->toBe($this->inactiveSubject->public_id);
});

it('paginates the subject collection', function () {
    Subject::create([
        'code' => 'FIS',
        'name' => 'Fisika',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/subjects?per_page=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 3);
});

it('does not allow users without an application role to access subjects', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/api/subjects')
        ->assertForbidden();
});

it('does not expose soft-deleted subjects through the API', function () {
    $this->activeSubject->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/subjects')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveSubject->public_id);

    $this->actingAs($this->admin)
        ->getJson("/api/subjects/{$this->activeSubject->public_id}")
        ->assertNotFound();
});

it('uses public_id rather than numeric id for route binding', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/subjects/{$this->activeSubject->id}")
        ->assertNotFound();
});

it('does not expose restore or force delete through the API', function () {
    $this->actingAs($this->admin)
        ->postJson("/api/subjects/{$this->activeSubject->public_id}/restore")
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->deleteJson("/api/subjects/{$this->activeSubject->public_id}/force")
        ->assertNotFound();
});
