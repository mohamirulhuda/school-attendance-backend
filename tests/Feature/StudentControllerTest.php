<?php

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\LearningGroup;
use App\Models\LearningGroupStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_piket', 'guard_name' => 'web']);
    Role::create(['name' => 'guru_mapel', 'guard_name' => 'web']);

    $this->activeStudent = Student::create([
        'nis' => '252001',
        'nisn' => '0099001001',
        'name' => 'Budi Santoso',
        'gender' => 'L',
        'birth_place' => 'Kediri',
        'birth_date' => '2010-01-10',
        'is_active' => true,
    ]);

    $this->inactiveStudent = Student::create([
        'nis' => '252002',
        'nisn' => '0099001002',
        'name' => 'Siti Aminah',
        'gender' => 'P',
        'birth_place' => null,
        'birth_date' => null,
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

it('allows all application roles to list students', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/students')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/students')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $this->activeStudent->public_id);
    }
});

it('returns a paginated student collection', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/students')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('allows all application roles to view an active student by public id', function () {
    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/students/{$this->activeStudent->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $this->activeStudent->public_id)
            ->assertJsonPath('data.nis', '252001')
            ->assertJsonPath('data.nisn', '0099001001')
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.gender', 'L')
            ->assertJsonPath('data.birth_place', 'Kediri')
            ->assertJsonPath('data.birth_date', '2010-01-10')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at');
    }
});

it('does not allow teachers to view an inactive student', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/students/{$this->inactiveStudent->public_id}")
            ->assertForbidden();
    }

    $this->actingAs($this->admin)
        ->getJson("/api/students/{$this->inactiveStudent->public_id}")
        ->assertSuccessful();
});

it('allows only admin to create students', function () {
    $payload = [
        'nis' => '252003',
        'nisn' => '0099001003',
        'name' => 'Andi Wijaya',
        'gender' => 'L',
        'birth_place' => 'Kediri',
        'birth_date' => '2010-05-20',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/students', $payload)
        ->assertCreated()
        ->assertJsonPath('data.nis', '252003')
        ->assertJsonPath('data.nisn', '0099001003')
        ->assertJsonPath('data.name', 'Andi Wijaya')
        ->assertJsonPath('data.gender', 'L')
        ->assertJsonPath('data.is_active', true);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/students', $payload)
            ->assertForbidden();
    }
});

it('allows only admin to update students', function () {
    $payload = [
        'name' => 'Budi Setiawan',
        'nis' => '252010',
        'nisn' => null,
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->patchJson("/api/students/{$this->activeStudent->public_id}", $payload)
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Budi Setiawan')
        ->assertJsonPath('data.nis', '252010')
        ->assertJsonPath('data.nisn', null)
        ->assertJsonPath('data.is_active', false);

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson("/api/students/{$this->activeStudent->public_id}", $payload)
            ->assertForbidden();
    }
});

it('rejects invalid student input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/students', [
            'nis' => str_repeat('1', 31),
            'nisn' => str_repeat('1', 21),
            'name' => '',
            'gender' => 'X',
            'birth_place' => str_repeat('A', 101),
            'birth_date' => 'not-a-date',
            'is_active' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'nis',
            'nisn',
            'name',
            'gender',
            'birth_place',
            'birth_date',
            'is_active',
        ]);
});

it('rejects duplicate nis and nisn when creating a student', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/students', [
            'nis' => $this->activeStudent->nis,
            'nisn' => '0099001099',
            'name' => 'Another Student',
            'gender' => 'L',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nis']);

    $this->actingAs($this->admin)
        ->postJson('/api/students', [
            'nis' => '252099',
            'nisn' => $this->activeStudent->nisn,
            'name' => 'Another Student',
            'gender' => 'L',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nisn']);
});

it('allows nullable student fields', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/students', [
            'nis' => null,
            'nisn' => null,
            'name' => 'Student Nullable',
            'gender' => 'P',
            'birth_place' => null,
            'birth_date' => null,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.nis', null)
        ->assertJsonPath('data.nisn', null)
        ->assertJsonPath('data.birth_place', null)
        ->assertJsonPath('data.birth_date', null);
});

it('allows a student to retain their own unique values on update', function () {
    $this->actingAs($this->admin)
        ->patchJson("/api/students/{$this->activeStudent->public_id}", [
            'nis' => $this->activeStudent->nis,
            'nisn' => $this->activeStudent->nisn,
        ])
        ->assertSuccessful();
});

it('filters students by name', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/students?name=Budi')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeStudent->public_id);
});

it('allows admin to filter students by active state', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/students?is_active=true')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeStudent->public_id);

    $this->actingAs($this->admin)
        ->getJson('/api/students?is_active=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveStudent->public_id);
});

it('does not let teachers bypass the active scope with the is_active filter', function () {
    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/students?is_active=false')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $this->activeStudent->public_id);
    }
});

it('filters students by current classroom relationship', function () {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => today()->subMonth(),
        'ends_at' => today()->addMonths(5),
        'is_active' => true,
    ]);

    $classroom = Classroom::create([
        'grade' => 10,
        'name' => 'A',
        'major' => 'IPA',
        'is_active' => true,
    ]);

    Enrollment::create([
        'academic_period_id' => $academicPeriod->id,
        'student_id' => $this->activeStudent->id,
        'classroom_id' => $classroom->id,
        'starts_at' => today()->subWeek(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/students?classroom={$classroom->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeStudent->public_id);
});

it('filters students by current learning group relationship', function () {
    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => Semester::Odd,
        'starts_at' => today()->subMonth(),
        'ends_at' => today()->addMonths(5),
        'is_active' => true,
    ]);

    $learningGroup = LearningGroup::create([
        'academic_period_id' => $academicPeriod->id,
        'name' => 'X IPA 1',
        'code' => 'XIPA1',
        'description' => null,
        'is_active' => true,
    ]);

    LearningGroupStudent::create([
        'learning_group_id' => $learningGroup->id,
        'student_id' => $this->activeStudent->id,
        'starts_at' => today()->subWeek(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/students?learning_group={$learningGroup->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->activeStudent->public_id);
});

it('returns students ordered by id ascending by default', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/students')
        ->assertSuccessful();

    expect($response->json('data.0.public_id'))
        ->toBe($this->activeStudent->public_id)
        ->and($response->json('data.1.public_id'))
        ->toBe($this->inactiveStudent->public_id);
});

it('supports name sorting when explicitly requested', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/students?sort=name')
        ->assertSuccessful();

    expect($response->json('data.0.name'))->toBe('Budi Santoso')
        ->and($response->json('data.1.name'))->toBe('Siti Aminah');
});

it('does not allow users without an application role to access students', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/api/students')
        ->assertForbidden();
});

it('does not expose soft-deleted students', function () {
    $this->activeStudent->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/students')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $this->inactiveStudent->public_id);

    $this->actingAs($this->admin)
        ->getJson("/api/students/{$this->activeStudent->public_id}")
        ->assertNotFound();
});

it('does not use the numeric id as the public route identifier', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/students/{$this->activeStudent->id}")
        ->assertNotFound();
});

it('does not expose delete as an API operation', function () {
    $this->actingAs($this->admin)
        ->deleteJson("/api/students/{$this->activeStudent->public_id}")
        ->assertMethodNotAllowed();
});
