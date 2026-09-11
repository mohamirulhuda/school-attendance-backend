<?php

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
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

    $this->inactivePeriod = AcademicPeriod::create([
        'academic_year' => '2025/2026',
        'semester' => Semester::Even,
        'starts_at' => today()->subYear(),
        'ends_at' => today()->subMonths(7),
        'is_active' => false,
    ]);

    $this->student = Student::create([
        'nis' => '252001',
        'nisn' => '0099001001',
        'name' => 'Budi Santoso',
        'gender' => 'L',
        'birth_place' => 'Kediri',
        'birth_date' => '2010-01-10',
        'is_active' => true,
    ]);

    $this->secondStudent = Student::create([
        'nis' => '252002',
        'nisn' => '0099001002',
        'name' => 'Siti Aminah',
        'gender' => 'P',
        'birth_place' => 'Kediri',
        'birth_date' => '2010-02-10',
        'is_active' => true,
    ]);

    $this->inactiveStudent = Student::create([
        'nis' => '252003',
        'nisn' => '0099001003',
        'name' => 'Inactive Student',
        'gender' => 'L',
        'birth_place' => null,
        'birth_date' => null,
        'is_active' => false,
    ]);

    $this->activeClassroom = Classroom::create([
        'grade' => 10,
        'name' => 'A',
        'major' => 'IPA',
        'is_active' => true,
    ]);

    $this->secondClassroom = Classroom::create([
        'grade' => 11,
        'name' => 'B',
        'major' => 'IPS',
        'is_active' => true,
    ]);

    $this->inactiveClassroom = Classroom::create([
        'grade' => 12,
        'name' => 'C',
        'major' => 'IPA',
        'is_active' => false,
    ]);

    $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    $this->admin->assignRole('admin');

    $this->piket = User::factory()->create(['email' => 'piket@example.com']);
    $this->piket->assignRole('guru_piket');

    $this->guruMapel = User::factory()->create([
        'email' => 'guru.mapel@example.com',
    ]);
    $this->guruMapel->assignRole('guru_mapel');
});

function enrollmentPayload($test, array $overrides = []): array
{
    return array_merge([
        'student_id' => $test->student->public_id,
        'classroom_id' => $test->activeClassroom->public_id,
        'starts_at' => today()->toDateString(),
        'ends_at' => null,
    ], $overrides);
}

it('allows all application roles to list enrollments', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today()->subDay(),
        'ends_at' => null,
    ]);

    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson('/api/enrollments')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }
});

it('returns a paginated enrollment collection', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/enrollments')
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('allows all application roles to view an enrollment by public id', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today(),
        'ends_at' => null,
    ]);

    foreach ([$this->admin, $this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->getJson("/api/enrollments/{$enrollment->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $enrollment->public_id)
            ->assertJsonPath('data.starts_at', today()->toDateString())
            ->assertJsonPath('data.ends_at', null)
            ->assertJsonPath(
                'data.academic_period.public_id',
                $this->academicPeriod->public_id,
            )
            ->assertJsonPath(
                'data.student.public_id',
                $this->student->public_id,
            )
            ->assertJsonPath(
                'data.classroom.public_id',
                $this->activeClassroom->public_id,
            )
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.academic_period.id')
            ->assertJsonMissingPath('data.student.id')
            ->assertJsonMissingPath('data.classroom.id');
    }
});

it('allows only admin to create enrollments', function () {
    $payload = enrollmentPayload($this, [
        'starts_at' => today()->toDateString(),
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', $payload)
        ->assertCreated()
        ->assertJsonPath(
            'data.academic_period.public_id',
            $this->academicPeriod->public_id,
        )
        ->assertJsonPath('data.student.public_id', $this->student->public_id)
        ->assertJsonPath(
            'data.classroom.public_id',
            $this->activeClassroom->public_id,
        );

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->postJson('/api/enrollments', $payload)
            ->assertForbidden();
    }
});

it('uses the active academic period instead of client input', function () {
    $payload = enrollmentPayload($this, [
        'starts_at' => today()->toDateString(),
        'academic_period_id' => $this->inactivePeriod->public_id,
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', $payload)
        ->assertCreated()
        ->assertJsonPath(
            'data.academic_period.public_id',
            $this->academicPeriod->public_id,
        );
});

it('does not accept academic_period_id as a create field', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/enrollments', [
            'student_id' => $this->student->public_id,
            'classroom_id' => $this->activeClassroom->public_id,
            'academic_period_id' => $this->inactivePeriod->public_id,
            'starts_at' => today()->toDateString(),
            'ends_at' => null,
        ])
        ->assertCreated();

    expect($response->json('data.academic_period.public_id'))
        ->toBe($this->academicPeriod->public_id);
});

it('rejects inactive student and classroom on create', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', enrollmentPayload($this, [
            'student_id' => $this->inactiveStudent->public_id,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['student_id']);

    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', enrollmentPayload($this, [
            'classroom_id' => $this->inactiveClassroom->public_id,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['classroom_id']);
});

it('requires an active academic period for create', function () {
    $this->academicPeriod->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', enrollmentPayload($this))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['academic_period']);
});

it('rejects invalid enrollment input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', [
            'student_id' => 'missing',
            'classroom_id' => 'missing',
            'starts_at' => 'not-a-date',
            'ends_at' => 'not-a-date',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'student_id',
            'classroom_id',
            'starts_at',
            'ends_at',
        ]);
});

it('rejects an enrollment whose end is not after its start', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', enrollmentPayload($this, [
            'starts_at' => '2026-09-10',
            'ends_at' => '2026-09-10',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);
});

it('prevents overlapping enrollments for the same student and academic period', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-15',
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', enrollmentPayload($this, [
            'starts_at' => '2026-09-10',
            'ends_at' => '2026-09-20',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['starts_at']);
});

it('allows a new enrollment exactly when the previous one ends', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-15',
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/enrollments', enrollmentPayload($this, [
            'classroom_id' => $this->secondClassroom->public_id,
            'starts_at' => '2026-09-15',
            'ends_at' => null,
        ]))
        ->assertCreated();
});

it('allows only admin to update enrollments', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => null,
    ]);

    $payload = ['classroom_id' => $this->secondClassroom->public_id];

    $this->actingAs($this->admin)
        ->patchJson("/api/enrollments/{$enrollment->public_id}", $payload)
        ->assertSuccessful()
        ->assertJsonPath(
            'data.classroom.public_id',
            $this->secondClassroom->public_id,
        );

    foreach ([$this->piket, $this->guruMapel] as $user) {
        $this->actingAs($user)
            ->patchJson("/api/enrollments/{$enrollment->public_id}", $payload)
            ->assertForbidden();
    }
});

it('does not allow academic period or student changes on update', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/enrollments/{$enrollment->public_id}", [
            'academic_period_id' => $this->inactivePeriod->public_id,
            'student_id' => $this->secondStudent->public_id,
        ])
        ->assertSuccessful()
        ->assertJsonPath(
            'data.academic_period.public_id',
            $this->academicPeriod->public_id,
        )
        ->assertJsonPath(
            'data.student.public_id',
            $this->student->public_id,
        );
});

it('rejects an inactive classroom on update', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/enrollments/{$enrollment->public_id}", [
            'classroom_id' => $this->inactiveClassroom->public_id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['classroom_id']);
});

it('supports ending an enrollment through a partial update', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/enrollments/{$enrollment->public_id}", [
            'ends_at' => '2026-09-15',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.ends_at', '2026-09-15');
});

it('prevents an update from creating an overlapping enrollment period', function () {
    $first = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-15',
    ]);

    $second = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->secondClassroom->id,
        'starts_at' => '2026-09-15',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/enrollments/{$first->public_id}", [
            'ends_at' => '2026-09-20',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at', 'starts_at']);
});

it('filters enrollments by academic period', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today()->subDay(),
        'ends_at' => null,
    ]);

    Enrollment::create([
        'academic_period_id' => $this->inactivePeriod->id,
        'student_id' => $this->secondStudent->id,
        'classroom_id' => $this->secondClassroom->id,
        'starts_at' => today()->subYear(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/enrollments?academic_period={$this->inactivePeriod->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.academic_period.public_id',
            $this->inactivePeriod->public_id,
        );
});

it('filters enrollments by student and classroom', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today()->subDay(),
        'ends_at' => null,
    ]);

    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->secondStudent->id,
        'classroom_id' => $this->secondClassroom->id,
        'starts_at' => today()->subDay(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/enrollments?student={$this->student->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.student.public_id', $this->student->public_id);

    $this->actingAs($this->admin)
        ->getJson("/api/enrollments?classroom={$this->secondClassroom->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.classroom.public_id',
            $this->secondClassroom->public_id,
        );
});

it('defaults enrollment list to the active academic period', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today()->subDay(),
        'ends_at' => null,
    ]);

    Enrollment::create([
        'academic_period_id' => $this->inactivePeriod->id,
        'student_id' => $this->secondStudent->id,
        'classroom_id' => $this->secondClassroom->id,
        'starts_at' => today()->subYear(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/enrollments')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.academic_period.public_id',
            $this->academicPeriod->public_id,
        );
});

it('filters enrollments by active_at using an exclusive end date', function () {
    Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-15',
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/enrollments?active_at=2026-09-14')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->admin)
        ->getJson('/api/enrollments?active_at=2026-09-15')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('orders enrollments by starts_at descending and id descending', function () {
    $older = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-05',
    ]);

    $newer = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->secondStudent->id,
        'classroom_id' => $this->secondClassroom->id,
        'starts_at' => '2026-09-10',
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/enrollments')
        ->assertSuccessful()
        ->assertJsonPath('data.0.public_id', $newer->public_id)
        ->assertJsonPath('data.1.public_id', $older->public_id);
});

it('does not allow users without an application role to access enrollments', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->actingAs($user)
        ->getJson('/api/enrollments')
        ->assertForbidden();
});

it('does not use numeric id as the public route identifier', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/enrollments/{$enrollment->id}")
        ->assertNotFound();
});

it('does not expose delete as an API operation', function () {
    $enrollment = Enrollment::create([
        'academic_period_id' => $this->academicPeriod->id,
        'student_id' => $this->student->id,
        'classroom_id' => $this->activeClassroom->id,
        'starts_at' => today(),
        'ends_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/enrollments/{$enrollment->public_id}")
        ->assertMethodNotAllowed();
});
