<?php

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
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
        'starts_at' => today()->subMonth(),
        'ends_at' => today()->addMonths(5),
        'is_active' => true,
    ]);

    $this->otherAcademicPeriod = AcademicPeriod::create([
        'academic_year' => '2025/2026',
        'semester' => Semester::Even,
        'starts_at' => today()->subMonths(8),
        'ends_at' => today()->subMonth(),
        'is_active' => false,
    ]);

    $this->activeGroup = LearningGroup::create([
        'academic_period_id' => $this->academicPeriod->id,
        'name' => 'X IPA 1',
        'code' => 'XIPA1',
        'description' => 'Science group',
        'is_active' => true,
    ]);

    $this->inactiveGroup = LearningGroup::create([
        'academic_period_id' => $this->academicPeriod->id,
        'name' => 'X IPA 2',
        'code' => 'XIPA2',
        'description' => null,
        'is_active' => false,
    ]);

    $this->otherPeriodGroup = LearningGroup::create([
        'academic_period_id' => $this->otherAcademicPeriod->id,
        'name' => 'X IPA 1',
        'code' => 'XIPA1-OLD',
        'description' => 'Historical group',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    $this->admin->assignRole('admin');

    $this->piket = User::factory()->create(['email' => 'piket@example.com']);
    $this->piket->assignRole('guru_piket');

    $this->guruMapel = User::factory()->create(['email' => 'guru.mapel@example.com']);
    $this->guruMapel->assignRole('guru_mapel');
});

it('allows all application roles to list learning groups', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/learning-groups')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }
});

it('allows all application roles to view a learning group by public id', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/learning-groups/{$this->activeGroup->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $this->activeGroup->public_id)
            ->assertJsonPath('data.name', 'X IPA 1')
            ->assertJsonPath('data.code', 'XIPA1')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.academic_period.public_id', $this->academicPeriod->public_id)
            ->assertJsonPath('data.academic_period.academic_year', '2026/2027')
            ->assertJsonPath('data.academic_period.semester', 'Ganjil')
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.deleted_at');
    }
});

it('allows admin to see inactive learning groups', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/learning-groups/{$this->inactiveGroup->public_id}")
        ->assertSuccessful();

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/learning-groups/{$this->inactiveGroup->public_id}")
            ->assertSuccessful();
    }
});

it('allows only admin to create learning groups', function () {
    $payload = [
        'academic_period_id' => $this->academicPeriod->public_id,
        'name' => 'X IPS 1',
        'code' => 'XIPS1',
        'description' => 'Social science group',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/learning-groups', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'X IPS 1')
        ->assertJsonPath('data.code', 'XIPS1')
        ->assertJsonPath('data.academic_period.public_id', $this->academicPeriod->public_id);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/learning-groups', $payload)
            ->assertForbidden();
    }
});

it('rejects invalid learning group input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/learning-groups', [
            'academic_period_id' => 'not-found',
            'name' => '',
            'code' => '',
            'description' => 123,
            'is_active' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'academic_period_id',
            'name',
            'code',
            'description',
            'is_active',
        ]);
});

it('rejects duplicate code within the same academic period', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/learning-groups', [
            'academic_period_id' => $this->academicPeriod->public_id,
            'name' => 'Duplicate Code',
            'code' => $this->activeGroup->code,
            'description' => null,
            'is_active' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('allows the same code in a different academic period', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/learning-groups', [
            'academic_period_id' => $this->otherAcademicPeriod->public_id,
            'name' => 'Same Code',
            'code' => $this->activeGroup->code,
            'description' => null,
            'is_active' => true,
        ])
        ->assertCreated();
});

it('rejects reuse of a code from a soft-deleted learning group', function () {
    $this->activeGroup->delete();

    $this->actingAs($this->admin)
        ->postJson('/api/learning-groups', [
            'academic_period_id' => $this->academicPeriod->public_id,
            'name' => 'Reused Code',
            'code' => 'XIPA1',
            'description' => null,
            'is_active' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('allows only admin to update learning groups', function () {
    $this->actingAs($this->admin)
        ->patchJson("/api/learning-groups/{$this->activeGroup->public_id}", [
            'name' => 'X IPA Updated',
            'code' => 'XIPA1B',
            'description' => 'Updated description',
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'X IPA Updated')
        ->assertJsonPath('data.code', 'XIPA1B')
        ->assertJsonPath('data.is_active', false);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson("/api/learning-groups/{$this->activeGroup->public_id}", [
                'name' => 'Not Allowed',
            ])
            ->assertForbidden();
    }
});

it('does not change academic period through update', function () {
    $this->actingAs($this->admin)
        ->patchJson("/api/learning-groups/{$this->activeGroup->public_id}", [
            'academic_period_id' => $this->otherAcademicPeriod->public_id,
            'name' => 'Updated Name',
        ])
        ->assertSuccessful();

    expect($this->activeGroup->refresh()->academic_period_id)
        ->toBe($this->academicPeriod->id);
});

it('rejects duplicate code when updating within the same academic period', function () {
    $this->actingAs($this->admin)
        ->patchJson("/api/learning-groups/{$this->activeGroup->public_id}", [
            'code' => $this->inactiveGroup->code,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('allows a learning group to retain its own code on update', function () {
    $this->actingAs($this->admin)
        ->patchJson("/api/learning-groups/{$this->activeGroup->public_id}", [
            'code' => $this->activeGroup->code,
        ])
        ->assertSuccessful();
});

it('filters learning groups by academic period', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/learning-groups?academic_period={$this->academicPeriod->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->actingAs($this->admin)
        ->getJson("/api/learning-groups?academic_period={$this->otherAcademicPeriod->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->otherPeriodGroup->public_id);
});

it('filters learning groups by active state', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/learning-groups?is_active=true')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->actingAs($this->admin)
        ->getJson('/api/learning-groups?is_active=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveGroup->public_id);
});

it('uses name ascending as the default sorting', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/learning-groups')
        ->assertSuccessful();

    expect($response->json('data.0.name'))->toBe('X IPA 1')
        ->and($response->json('data.1.name'))->toBe('X IPA 1')
        ->and($response->json('data.2.name'))->toBe('X IPA 2');
});

it('does not allow users without an application role to access learning groups', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->actingAs($user)
        ->getJson('/api/learning-groups')
        ->assertForbidden();
});

it('does not expose a numeric id as the public route identifier', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/learning-groups/{$this->activeGroup->id}")
        ->assertNotFound();
});

it('does not expose soft-deleted learning groups', function () {
    $this->activeGroup->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/learning-groups')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->actingAs($this->admin)
        ->getJson("/api/learning-groups/{$this->activeGroup->public_id}")
        ->assertNotFound();
});

it('allows only admin to soft-delete learning groups', function () {
    $this->actingAs($this->piket)
        ->deleteJson("/api/learning-groups/{$this->activeGroup->public_id}")
        ->assertForbidden();

    $this->actingAs($this->guruMapel)
        ->deleteJson("/api/learning-groups/{$this->activeGroup->public_id}")
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->deleteJson("/api/learning-groups/{$this->activeGroup->public_id}")
        ->assertNoContent();

    expect($this->activeGroup->refresh()->trashed())->toBeTrue();
});

it('does not expose restore as an API operation', function () {
    $this->activeGroup->delete();

    $this->actingAs($this->admin)
        ->postJson("/api/learning-groups/{$this->activeGroup->public_id}/restore")
        ->assertNotFound();
});

it('provides the dedicated membership endpoint', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/learning-groups/{$this->activeGroup->public_id}/memberships")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});
