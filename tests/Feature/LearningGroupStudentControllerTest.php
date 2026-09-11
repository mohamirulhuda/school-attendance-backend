<?php

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\LearningGroupStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'guru_mapel', 'guru_piket'] as $role) {
        Role::create(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->guruMapel = User::factory()->create();
    $this->guruMapel->assignRole('guru_mapel');

    $this->guruPiket = User::factory()->create();
    $this->guruPiket->assignRole('guru_piket');

    $this->academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd->value,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->learningGroup = LearningGroup::create([
        'academic_period_id' => $this->academicPeriod->id,
        'name' => 'X IPA 1',
        'code' => 'XIPA1',
        'description' => null,
        'is_active' => true,
    ]);

    $this->otherLearningGroup = LearningGroup::create([
        'academic_period_id' => $this->academicPeriod->id,
        'name' => 'X IPA 2',
        'code' => 'XIPA2',
        'description' => null,
        'is_active' => true,
    ]);

    $this->student = Student::create([
        'nis' => '1001',
        'nisn' => '2001',
        'name' => 'Budi Santoso',
        'gender' => 'L',
        'birth_place' => null,
        'birth_date' => null,
        'is_active' => true,
    ]);

    $this->otherStudent = Student::create([
        'nis' => '1002',
        'nisn' => '2002',
        'name' => 'Siti Aminah',
        'gender' => 'P',
        'birth_place' => null,
        'birth_date' => null,
        'is_active' => true,
    ]);
});

function membershipPayload($test, array $overrides = []): array
{
    return array_merge([
        'learning_group_id' => $test->learningGroup->public_id,
        'student_id' => $test->student->public_id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ], $overrides);
}

it('allows all application roles to list memberships', function () {
    LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    foreach ([$this->admin, $this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->getJson('/api/learning-group-memberships')
            ->assertSuccessful()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(1, 'data');
    }
});

it('allows all application roles to view a membership by public id', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    foreach ([$this->admin, $this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->getJson("/api/learning-group-memberships/{$membership->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $membership->public_id)
            ->assertJsonPath('data.starts_at', '2026-07-01')
            ->assertJsonPath('data.ends_at', null)
            ->assertJsonPath(
                'data.learning_group.public_id',
                $this->learningGroup->public_id,
            )
            ->assertJsonPath('data.learning_group.name', 'X IPA 1')
            ->assertJsonPath('data.learning_group.code', 'XIPA1')
            ->assertJsonPath(
                'data.student.public_id',
                $this->student->public_id,
            )
            ->assertJsonPath('data.student.name', 'Budi Santoso')
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');
    }
});

it('allows only admin to create memberships', function () {
    $payload = membershipPayload($this);

    $this->actingAs($this->admin)
        ->postJson('/api/learning-group-memberships', $payload)
        ->assertCreated();

    foreach ([$this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->postJson('/api/learning-group-memberships', membershipPayload(
                $this,
                ['student_id' => $this->otherStudent->public_id],
            ))
            ->assertForbidden();
    }
});

it('allows only admin to update memberships', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    $payload = ['ends_at' => '2026-08-01'];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/learning-group-memberships/{$membership->public_id}",
            $payload,
        )
        ->assertSuccessful()
        ->assertJsonPath('data.ends_at', '2026-08-01');

    foreach ([$this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/learning-group-memberships/{$membership->public_id}",
                $payload,
            )
            ->assertForbidden();
    }
});

it('allows only admin to soft delete memberships', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    foreach ([$this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->deleteJson("/api/learning-group-memberships/{$membership->public_id}")
            ->assertForbidden();
    }

    $this->actingAs($this->admin)
        ->deleteJson("/api/learning-group-memberships/{$membership->public_id}")
        ->assertNoContent();

    expect(LearningGroupStudent::withTrashed()->find($membership->id)->deleted_at)
        ->not->toBeNull();
});

it('rejects invalid membership input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/learning-group-memberships', [
            'learning_group_id' => 'not-found',
            'student_id' => 'not-found',
            'starts_at' => null,
            'ends_at' => '2026-06-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'learning_group_id',
            'student_id',
            'starts_at',
        ]);
});

it('rejects an end date that is not after the start date', function () {
    $this->actingAs($this->admin)
        ->postJson(
            '/api/learning-group-memberships',
            membershipPayload($this, ['ends_at' => '2026-07-01']),
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);
});

it('allows multiple active memberships for different learning groups', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/learning-group-memberships', membershipPayload($this))
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson('/api/learning-group-memberships', membershipPayload(
            $this,
            ['learning_group_id' => $this->otherLearningGroup->public_id],
        ))
        ->assertCreated();
});

it('rejects overlapping memberships for the same student and learning group', function () {
    LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-08-15',
    ]);

    $this->actingAs($this->admin)
        ->postJson(
            '/api/learning-group-memberships',
            membershipPayload($this, [
                'starts_at' => '2026-08-01',
                'ends_at' => '2026-09-01',
            ]),
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['starts_at']);
});

it('allows a membership to start when the previous membership ends', function () {
    LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-08-15',
    ]);

    $this->actingAs($this->admin)
        ->postJson(
            '/api/learning-group-memberships',
            membershipPayload($this, [
                'starts_at' => '2026-08-15',
                'ends_at' => null,
            ]),
        )
        ->assertCreated();
});

it('ignores soft-deleted memberships for active overlap checks', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);
    $membership->delete();

    $this->actingAs($this->admin)
        ->postJson('/api/learning-group-memberships', membershipPayload($this))
        ->assertCreated();
});

it('allows a membership to update its own values without self-overlap', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-08-15',
    ]);

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/learning-group-memberships/{$membership->public_id}",
            [
                'starts_at' => '2026-07-15',
                'ends_at' => '2026-08-15',
            ],
        )
        ->assertSuccessful();
});

it('can update the learning group and student together', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/learning-group-memberships/{$membership->public_id}",
            [
                'learning_group_id' => $this->otherLearningGroup->public_id,
                'student_id' => $this->otherStudent->public_id,
                'starts_at' => '2026-09-01',
                'ends_at' => null,
            ],
        )
        ->assertSuccessful()
        ->assertJsonPath(
            'data.learning_group.public_id',
            $this->otherLearningGroup->public_id,
        )
        ->assertJsonPath(
            'data.student.public_id',
            $this->otherStudent->public_id,
        );
});

it('filters memberships by learning group, student, and active date', function () {
    $active = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    LearningGroupStudent::create([
        'learning_group_id' => $this->otherLearningGroup->id,
        'student_id' => $this->otherStudent->id,
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-06-30',
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/learning-group-memberships?learning_group={$this->learningGroup->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $active->public_id);

    $this->actingAs($this->admin)
        ->getJson("/api/learning-group-memberships?student={$this->student->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->admin)
        ->getJson('/api/learning-group-memberships?active_at=2026-09-01')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $active->public_id);
});

it('orders memberships by starts_at descending and id descending', function () {
    $older = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-08-01',
    ]);

    $newer = LearningGroupStudent::create([
        'learning_group_id' => $this->otherLearningGroup->id,
        'student_id' => $this->otherStudent->id,
        'starts_at' => '2026-09-01',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/learning-group-memberships')
        ->assertSuccessful()
        ->assertJsonPath('data.0.public_id', $newer->public_id)
        ->assertJsonPath('data.1.public_id', $older->public_id);
});

it('does not expose soft-deleted memberships in normal endpoints', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);
    $membership->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/learning-group-memberships')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->actingAs($this->admin)
        ->getJson("/api/learning-group-memberships/{$membership->public_id}")
        ->assertNotFound();
});

it('does not use numeric ids for route binding', function () {
    $membership = LearningGroupStudent::create([
        'learning_group_id' => $this->learningGroup->id,
        'student_id' => $this->student->id,
        'starts_at' => '2026-07-01',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/learning-group-memberships/{$membership->id}")
        ->assertNotFound();
});

it('does not allow users without an application role to access memberships', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/learning-group-memberships')
        ->assertForbidden();
});
