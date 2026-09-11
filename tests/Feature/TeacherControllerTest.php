<?php

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->activeTeacher = Teacher::create([
        'nip' => '198001010001',
        'name' => 'Budi Santoso',
        'nickname' => 'Budi',
        'gender' => 'L',
        'title_prefix' => null,
        'title_suffix' => 'S.Pd.',
        'email' => 'budi@example.com',
        'phone' => '081234567890',
        'address' => 'Kediri',
        'is_active' => true,
    ]);

    $this->inactiveTeacher = Teacher::create([
        'nip' => '198001010002',
        'name' => 'Siti Aminah',
        'nickname' => 'Siti',
        'gender' => 'P',
        'title_prefix' => 'Dra.',
        'title_suffix' => null,
        'email' => 'siti@example.com',
        'phone' => null,
        'address' => null,
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

it('allows all application roles to list teachers', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/teachers')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/teachers')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.public_id',
                $this->activeTeacher->public_id,
            );
    }
});

it('returns paginated teacher collections', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/teachers')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('allows all application roles to view an active teacher by public id', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/teachers/{$this->activeTeacher->public_id}")
            ->assertSuccessful()
            ->assertJsonPath(
                'data.public_id',
                $this->activeTeacher->public_id,
            )
            ->assertJsonPath('data.nip', '198001010001')
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.nickname', 'Budi')
            ->assertJsonPath('data.gender', 'L')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');
    }
});

it('does not allow teachers to view an inactive teacher', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/teachers/{$this->inactiveTeacher->public_id}")
            ->assertForbidden();
    }

    $this->actingAs($this->admin)
        ->getJson("/api/teachers/{$this->inactiveTeacher->public_id}")
        ->assertSuccessful();
});

it('allows only admin to create teachers', function () {
    $payload = [
        'nip' => '198001010003',
        'name' => 'Andi Wijaya',
        'nickname' => 'Andi',
        'gender' => 'L',
        'title_prefix' => null,
        'title_suffix' => 'S.Pd.',
        'email' => 'andi@example.com',
        'phone' => null,
        'address' => null,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/teachers', $payload)
        ->assertCreated()
        ->assertJsonPath('data.nip', '198001010003')
        ->assertJsonPath('data.name', 'Andi Wijaya')
        ->assertJsonPath('data.nickname', 'Andi')
        ->assertJsonPath('data.gender', 'L')
        ->assertJsonPath('data.is_active', true);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/teachers', $payload)
            ->assertForbidden();
    }
});

it('allows only admin to update teachers', function () {
    $payload = [
        'name' => 'Budi Setiawan',
        'nickname' => 'Budi Set',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/teachers/{$this->activeTeacher->public_id}",
            $payload,
        )
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Budi Setiawan')
        ->assertJsonPath('data.nickname', 'Budi Set')
        ->assertJsonPath('data.is_active', false);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/teachers/{$this->activeTeacher->public_id}",
                $payload,
            )
            ->assertForbidden();
    }
});

it('rejects invalid teacher input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/teachers', [
            'nip' => str_repeat('1', 31),
            'name' => '',
            'nickname' => '',
            'gender' => 'X',
            'email' => 'not-an-email',
            'phone' => str_repeat('1', 31),
            'is_active' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'nip',
            'name',
            'nickname',
            'gender',
            'email',
            'phone',
            'is_active',
        ]);
});

it('rejects duplicate nip and email when creating a teacher', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/teachers', [
            'nip' => $this->activeTeacher->nip,
            'name' => 'Another Teacher',
            'nickname' => 'Another',
            'gender' => 'L',
            'email' => 'another@example.com',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nip']);

    $this->actingAs($this->admin)
        ->postJson('/api/teachers', [
            'nip' => '198001010099',
            'name' => 'Another Teacher',
            'nickname' => 'Another',
            'gender' => 'L',
            'email' => $this->activeTeacher->email,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('allows nullable teacher fields', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/teachers', [
            'nip' => null,
            'name' => 'Teacher Nullable',
            'nickname' => 'Nullable',
            'gender' => 'P',
            'title_prefix' => null,
            'title_suffix' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.nip', null)
        ->assertJsonPath('data.email', null);
});

it('allows a teacher to update their own unique values without triggering self uniqueness', function () {
    $this->actingAs($this->admin)
        ->patchJson(
            "/api/teachers/{$this->activeTeacher->public_id}",
            [
                'nip' => $this->activeTeacher->nip,
                'email' => $this->activeTeacher->email,
            ],
        )
        ->assertSuccessful();
});

it('filters teachers by supported fields', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/teachers?name=Budi')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeTeacher->public_id);

    $this->actingAs($this->admin)
        ->getJson('/api/teachers?nickname=Siti')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveTeacher->public_id);

    $this->actingAs($this->admin)
        ->getJson('/api/teachers?email=siti@example.com')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveTeacher->public_id);
});

it('allows admin to filter teachers by active state', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/teachers?is_active=true')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeTeacher->public_id);

    $this->actingAs($this->admin)
        ->getJson('/api/teachers?is_active=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveTeacher->public_id);
});

it('does not let teachers bypass the active scope with the is_active filter', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/teachers?is_active=false')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.public_id',
                $this->activeTeacher->public_id,
            );
    }
});

it('returns teachers ordered by id ascending', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/teachers')
        ->assertSuccessful();

    expect($response->json('data.0.public_id'))
        ->toBe($this->activeTeacher->public_id)
        ->and($response->json('data.1.public_id'))
        ->toBe($this->inactiveTeacher->public_id);
});

it('does not allow users without an application role to access teachers', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/api/teachers')
        ->assertForbidden();
});

it('does not expose deleted teachers through the API', function () {
    $this->activeTeacher->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/teachers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.public_id',
            $this->inactiveTeacher->public_id,
        );

    $this->actingAs($this->admin)
        ->getJson("/api/teachers/{$this->activeTeacher->public_id}")
        ->assertNotFound();
});

it('does not accept internal teacher id as the public route identifier', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/teachers/{$this->activeTeacher->id}")
        ->assertNotFound();
});

it('does not expose delete as an API operation', function () {
    $this->actingAs($this->admin)
        ->deleteJson("/api/teachers/{$this->activeTeacher->public_id}")
        ->assertMethodNotAllowed();
});
