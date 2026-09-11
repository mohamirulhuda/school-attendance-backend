<?php

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
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

    $this->otherAcademicPeriod = AcademicPeriod::create([
        'academic_year' => '2025/2026',
        'semester' => Semester::Even->value,
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-06-30',
        'is_active' => false,
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

    $this->otherPeriodLearningGroup = LearningGroup::create([
        'academic_period_id' => $this->otherAcademicPeriod->id,
        'name' => 'IX IPA 1',
        'code' => 'IXIPA1',
        'description' => null,
        'is_active' => true,
    ]);

    $this->subject = Subject::create([
        'code' => 'MAT',
        'name' => 'Matematika',
        'is_active' => true,
    ]);

    $this->otherSubject = Subject::create([
        'code' => 'FIS',
        'name' => 'Fisika',
        'is_active' => true,
    ]);

    $this->teacher = Teacher::create([
        'nip' => '198001010001',
        'name' => 'Budi Santoso',
        'nickname' => 'Budi',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $this->otherTeacher = Teacher::create([
        'nip' => '198001010002',
        'name' => 'Siti Aminah',
        'nickname' => 'Siti',
        'gender' => 'P',
        'is_active' => true,
    ]);

    $this->period = Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    $this->otherPeriod = Period::create([
        'number' => 2,
        'name' => 'Jam 2',
        'starts_at' => '07:45',
        'ends_at' => '08:30',
        'is_active' => true,
    ]);
});

function schedulePayload($test, array $overrides = []): array
{
    return array_merge([
        'academic_period_id' => $test->academicPeriod->public_id,
        'learning_group_id' => $test->learningGroup->public_id,
        'subject_id' => $test->subject->public_id,
        'teacher_id' => $test->teacher->public_id,
        'period_id' => $test->period->public_id,
        'day_of_week' => DayOfWeek::Monday->value,
        'is_active' => true,
    ], $overrides);
}

it('allows all application roles to list schedules', function () {
    Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->learningGroup->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'period_id' => $this->period->id,
        'day_of_week' => DayOfWeek::Monday,
        'is_active' => true,
    ]);

    foreach ([$this->admin, $this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->getJson('/api/schedules')
            ->assertSuccessful()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(1, 'data');
    }
});

it('allows all application roles to view a schedule by public id', function () {
    $schedule = createSchedule();

    foreach ([$this->admin, $this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->getJson("/api/schedules/{$schedule->public_id}")
            ->assertSuccessful()
            ->assertJsonPath('data.public_id', $schedule->public_id)
            ->assertJsonPath('data.day_of_week', 1)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.academic_period.public_id', $this->academicPeriod->public_id)
            ->assertJsonPath('data.academic_period.academic_year', '2026/2027')
            ->assertJsonPath('data.academic_period.semester', Semester::Odd->value)
            ->assertJsonPath('data.learning_group.public_id', $this->learningGroup->public_id)
            ->assertJsonPath('data.learning_group.name', 'X IPA 1')
            ->assertJsonPath('data.subject.public_id', $this->subject->public_id)
            ->assertJsonPath('data.subject.code', 'MAT')
            ->assertJsonPath('data.teacher.public_id', $this->teacher->public_id)
            ->assertJsonPath('data.teacher.nickname', 'Budi')
            ->assertJsonPath('data.period.public_id', $this->period->public_id)
            ->assertJsonPath('data.period.number', 1)
            ->assertJsonPath('data.period.starts_at', '07:00')
            ->assertJsonPath('data.period.ends_at', '07:45')
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at')
            ->assertJsonMissingPath('data.attendance_sessions');
    }
});

it('allows only admin to create schedules', function () {
    $payload = schedulePayload($this);

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', $payload)
        ->assertCreated()
        ->assertJsonPath('data.day_of_week', 1)
        ->assertJsonPath('data.is_active', true);

    foreach ([$this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->postJson('/api/schedules', schedulePayload(
                $this,
                ['day_of_week' => DayOfWeek::Tuesday->value]
            ))
            ->assertForbidden();
    }
});

it('allows only admin to update schedules', function () {
    $schedule = createSchedule();

    $payload = [
        'day_of_week' => DayOfWeek::Tuesday->value,
        'subject_id' => $this->otherSubject->public_id,
        'teacher_id' => $this->otherTeacher->public_id,
        'period_id' => $this->otherPeriod->public_id,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/schedules/{$schedule->public_id}",
            $payload
        )
        ->assertSuccessful()
        ->assertJsonPath('data.day_of_week', 2)
        ->assertJsonPath('data.subject.public_id', $this->otherSubject->public_id)
        ->assertJsonPath('data.teacher.public_id', $this->otherTeacher->public_id)
        ->assertJsonPath('data.period.public_id', $this->otherPeriod->public_id);

    foreach ([$this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->patchJson(
                "/api/schedules/{$schedule->public_id}",
                ['day_of_week' => DayOfWeek::Tuesday->value]
            )
            ->assertForbidden();
    }
});

it('does not accept academic period on update', function () {
    $schedule = createSchedule();

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/schedules/{$schedule->public_id}",
            ['academic_period_id' => $this->otherAcademicPeriod->public_id]
        )
        ->assertSuccessful()
        ->assertJsonPath(
            'data.academic_period.public_id',
            $this->academicPeriod->public_id
        );
});

it('rejects invalid schedule input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/schedules', [
            'academic_period_id' => 'not-found',
            'learning_group_id' => 'not-found',
            'subject_id' => 'not-found',
            'teacher_id' => 'not-found',
            'period_id' => 'not-found',
            'day_of_week' => 8,
            'is_active' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'academic_period_id',
            'learning_group_id',
            'subject_id',
            'teacher_id',
            'period_id',
            'day_of_week',
            'is_active',
        ]);
});

it('requires all create fields except is_active', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/schedules', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'academic_period_id',
            'learning_group_id',
            'subject_id',
            'teacher_id',
            'period_id',
            'day_of_week',
        ]);
});

it('rejects inactive dependencies on create', function () {
    $inactiveCases = [
        ['academic_period_id', $this->otherAcademicPeriod],
        ['learning_group_id', LearningGroup::create([
            'academic_period_id' => $this->academicPeriod->id,
            'name' => 'Inactive Group',
            'code' => 'INACTIVE',
            'is_active' => false,
        ])],
        ['subject_id', Subject::create([
            'code' => 'BIO',
            'name' => 'Biologi',
            'is_active' => false,
        ])],
        ['teacher_id', Teacher::create([
            'nip' => '198001010003',
            'name' => 'Inactive Teacher',
            'nickname' => 'Inactive',
            'gender' => 'L',
            'is_active' => false,
        ])],
        ['period_id', Period::create([
            'number' => 3,
            'name' => 'Inactive Period',
            'starts_at' => '08:30',
            'ends_at' => '09:15',
            'is_active' => false,
        ])],
    ];

    foreach ($inactiveCases as [$field, $model]) {
        $payload = schedulePayload(
            $this,
            [$field => $model->public_id]
        );

        if ($field === 'academic_period_id') {
            $payload['learning_group_id'] = $this->otherPeriodLearningGroup->public_id;
        }

        $this->actingAs($this->admin)
            ->postJson('/api/schedules', $payload)
            ->assertUnprocessable();
    }
});

it('rejects a learning group from another academic period', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload(
            $this,
            ['learning_group_id' => $this->otherPeriodLearningGroup->public_id]
        ))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['learning_group_id']);
});

it('rejects teacher slot conflicts', function () {
    createSchedule();

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload(
            $this,
            [
                'learning_group_id' => $this->otherLearningGroup->public_id,
            ]
        ))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['teacher_id']);
});

it('rejects learning group slot conflicts', function () {
    createSchedule();

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload(
            $this,
            [
                'teacher_id' => $this->otherTeacher->public_id,
            ]
        ))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['learning_group_id']);
});

it('allows the same period for different learning groups', function () {
    createSchedule();

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload(
            $this,
            [
                'learning_group_id' => $this->otherLearningGroup->public_id,
                'teacher_id' => $this->otherTeacher->public_id,
            ]
        ))
        ->assertCreated();
});

it('allows the same subject repeatedly in an academic period', function () {
    createSchedule();

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload(
            $this,
            [
                'learning_group_id' => $this->otherLearningGroup->public_id,
                'teacher_id' => $this->otherTeacher->public_id,
                'day_of_week' => DayOfWeek::Tuesday->value,
            ]
        ))
        ->assertCreated();
});

it('allows inactive schedules to be replaced by a new active schedule', function () {
    $schedule = createSchedule();
    $schedule->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload($this))
        ->assertCreated();
});

it('ignores soft-deleted schedules for conflicts and normal listing', function () {
    $schedule = createSchedule();
    $schedule->delete();

    $this->actingAs($this->admin)
        ->postJson('/api/schedules', schedulePayload($this))
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson('/api/schedules')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('rejects inactive dependencies on update', function () {
    $schedule = createSchedule();
    $this->subject->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->patchJson(
            "/api/schedules/{$schedule->public_id}",
            ['subject_id' => $this->subject->public_id]
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['subject_id']);
});

it('allows only admin to soft delete schedules', function () {
    $schedule = createSchedule();

    foreach ([$this->guruMapel, $this->guruPiket] as $user) {
        $this->actingAs($user)
            ->deleteJson("/api/schedules/{$schedule->public_id}")
            ->assertForbidden();
    }

    $this->actingAs($this->admin)
        ->deleteJson("/api/schedules/{$schedule->public_id}")
        ->assertNoContent();

    expect(Schedule::withTrashed()->find($schedule->id)->deleted_at)
        ->not->toBeNull();
});

it('filters schedules by supported fields', function () {
    $first = createSchedule();

    $second = Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->otherLearningGroup->id,
        'subject_id' => $this->otherSubject->id,
        'teacher_id' => $this->otherTeacher->id,
        'period_id' => $this->otherPeriod->id,
        'day_of_week' => DayOfWeek::Tuesday,
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->getJson("/api/schedules?academic_period={$this->academicPeriod->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->actingAs($this->admin)
        ->getJson("/api/schedules?learning_group={$this->otherLearningGroup->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $second->public_id);

    $this->actingAs($this->admin)
        ->getJson("/api/schedules?teacher={$this->otherTeacher->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->admin)
        ->getJson("/api/schedules?subject={$this->otherSubject->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->admin)
        ->getJson("/api/schedules?period={$this->otherPeriod->public_id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->admin)
        ->getJson('/api/schedules?day_of_week=2')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->admin)
        ->getJson('/api/schedules?is_active=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('defaults schedule listing to the active academic period', function () {
    createSchedule();

    Schedule::create([
        'academic_period_id' => $this->otherAcademicPeriod->id,
        'learning_group_id' => $this->otherPeriodLearningGroup->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->otherTeacher->id,
        'period_id' => $this->period->id,
        'day_of_week' => DayOfWeek::Monday,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/schedules')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('orders schedules by day and period number', function () {
    $laterPeriod = Period::create([
        'number' => 3,
        'name' => 'Jam 3',
        'starts_at' => '08:30',
        'ends_at' => '09:15',
        'is_active' => true,
    ]);

    $tuesday = Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->otherLearningGroup->id,
        'subject_id' => $this->otherSubject->id,
        'teacher_id' => $this->otherTeacher->id,
        'period_id' => $laterPeriod->id,
        'day_of_week' => DayOfWeek::Tuesday,
        'is_active' => true,
    ]);

    $mondayPeriod2 = Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->otherLearningGroup->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'period_id' => $this->otherPeriod->id,
        'day_of_week' => DayOfWeek::Monday,
        'is_active' => true,
    ]);

    $mondayPeriod1 = createSchedule();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/schedules')
        ->assertSuccessful();

    expect($response->json('data.0.public_id'))->toBe($mondayPeriod1->public_id)
        ->and($response->json('data.1.public_id'))->toBe($mondayPeriod2->public_id)
        ->and($response->json('data.2.public_id'))->toBe($tuesday->public_id);
});

it('paginates schedule collections', function () {
    createSchedule();

    $second = Schedule::create([
        'academic_period_id' => $this->academicPeriod->id,
        'learning_group_id' => $this->otherLearningGroup->id,
        'subject_id' => $this->otherSubject->id,
        'teacher_id' => $this->otherTeacher->id,
        'period_id' => $this->otherPeriod->id,
        'day_of_week' => DayOfWeek::Tuesday,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/schedules?per_page=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2);
});

it('does not allow users without an application role to access schedules', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/schedules')
        ->assertForbidden();
});

it('uses public_id rather than numeric id for route binding', function () {
    $schedule = createSchedule();

    $this->actingAs($this->admin)
        ->getJson("/api/schedules/{$schedule->id}")
        ->assertNotFound();
});

it('does not expose restore or force delete through the API', function () {
    $schedule = createSchedule();

    $this->actingAs($this->admin)
        ->postJson("/api/schedules/{$schedule->public_id}/restore")
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->deleteJson("/api/schedules/{$schedule->public_id}/force")
        ->assertNotFound();
});

it('does not expose internal fields in the schedule resource', function () {
    $schedule = createSchedule();

    $this->actingAs($this->admin)
        ->getJson("/api/schedules/{$schedule->public_id}")
        ->assertSuccessful()
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.deleted_at')
        ->assertJsonMissingPath('data.attendance_sessions');
});

function createScheduleForTest($test): Schedule
{
    return Schedule::create([
        'academic_period_id' => $test->academicPeriod->id,
        'learning_group_id' => $test->learningGroup->id,
        'subject_id' => $test->subject->id,
        'teacher_id' => $test->teacher->id,
        'period_id' => $test->period->id,
        'day_of_week' => DayOfWeek::Monday,
        'is_active' => true,
    ]);
}

function createSchedule($test = null): Schedule
{
    $test ??= test();

    return createScheduleForTest($test);
}
