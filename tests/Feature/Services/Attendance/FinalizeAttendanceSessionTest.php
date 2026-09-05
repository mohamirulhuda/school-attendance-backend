<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Attendance\FinalizeAttendanceSession;
use App\Services\Attendance\StartAttendanceSession;
use App\Services\Attendance\UpdateAttendanceStatus;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('finalizes a draft attendance session and records an audit log', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Draft);

    $finalizedSession = app(FinalizeAttendanceSession::class)->execute(
        $session->id,
    );

    expect($finalizedSession->status)
        ->toBe(AttendanceSessionStatus::Finalized);

    expect($finalizedSession->finalized_by)
        ->toBe($data['user']->id);

    expect($finalizedSession->finalized_at)
        ->not->toBeNull();

    expect($finalizedSession->attendanceRecords)
        ->not->toBeEmpty();

    expect($finalizedSession->attendanceRecords->every(
        fn($record) => $record->status === AttendanceStatus::Present
    ))->toBeTrue();

    $audit = AuditLog::query()
        ->where('auditable_type', AttendanceSession::class)
        ->where('auditable_id', $session->id)
        ->where('action', 'finalize')
        ->first();

    expect($audit)->not->toBeNull();

    expect($audit->old_values)
        ->toMatchArray([
            'status' => 'draft',
        ]);

    expect($audit->new_values)
        ->toMatchArray([
            'status' => 'finalized',
            'finalized_by' => $data['user']->id,
        ]);
});

it('rejects finalizing an already finalized attendance session', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $finalizedSession = app(FinalizeAttendanceSession::class)->execute(
        $session->id,
    );

    $originalFinalizedAt = $finalizedSession->finalized_at;
    $originalFinalizedBy = $finalizedSession->finalized_by;

    expect(fn () => app(FinalizeAttendanceSession::class)->execute(
        $session->id,
    ))->toThrow(ValidationException::class);

    $session->refresh();

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Finalized);

    expect($session->finalized_by)
        ->toBe($originalFinalizedBy);

    expect($session->finalized_at)
        ->toEqual($originalFinalizedAt);

    expect(
        AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'finalize')
            ->where('auditable_id', $session->id)
            ->count()
    )->toBe(1);
});

it('rejects finalizing by a non-owner guru_mapel', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $otherTeacher = Teacher::create([
        'name' => 'Other Teacher',
        'email' => 'other.teacher@example.com',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $otherUser = User::factory()->create([
        'email' => $otherTeacher->email,
    ]);

    $otherUser->assignRole('guru_mapel');

    Auth::login($otherUser);

    expect(fn () => app(FinalizeAttendanceSession::class)->execute(
        $session->id,
    ))->toThrow(AuthorizationException::class);

    $session->refresh();

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Draft);

    expect($session->finalized_by)
        ->toBeNull();

    expect($session->finalized_at)
        ->toBeNull();

    expect(
        AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'finalize')
            ->where('auditable_id', $session->id)
            ->count()
    )->toBe(0);
});

it('rejects finalizing a session without attendance records', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $session->attendanceRecords()->delete();

    expect(fn () => app(FinalizeAttendanceSession::class)->execute(
        $session->id,
    ))->toThrow(ValidationException::class);

    $session->refresh();

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Draft);

    expect($session->finalized_by)
        ->toBeNull();

    expect($session->finalized_at)
        ->toBeNull();

    expect(
        AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'finalize')
            ->where('auditable_id', $session->id)
            ->count()
    )->toBe(0);
});

it('rejects finalizing when attendance records are incomplete', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $record = $session->attendanceRecords()->first();

    $record->delete();

    expect($session->attendanceRecords()->count())
        ->toBe(1);

    expect(fn () => app(FinalizeAttendanceSession::class)->execute(
        $session->id,
    ))->toThrow(ValidationException::class);

    $session->refresh();

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Draft);

    expect($session->finalized_by)
        ->toBeNull();

    expect($session->finalized_at)
        ->toBeNull();

    expect(
        AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'finalize')
            ->where('auditable_id', $session->id)
            ->count()
    )->toBe(0);
});

it('allows correction after the session is finalized', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $record = $session->attendanceRecords()->first();

    $session = app(\App\Services\Attendance\FinalizeAttendanceSession::class)
        ->execute($session->id);

    $originalFinalizedBy = $session->finalized_by;
    $originalFinalizedAt = $session->finalized_at;

    $updatedRecord = app(UpdateAttendanceStatus::class)->execute(
        $record->id,
        AttendanceStatus::Sick,
        'Demam',
    );

    expect($updatedRecord->status)
        ->toBe(AttendanceStatus::Sick);

    expect($updatedRecord->note)
        ->toBe('Demam');

    $session->refresh();

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Finalized);

    expect($session->finalized_by)
        ->toBe($originalFinalizedBy);

    expect($session->finalized_at)
        ->toEqual($originalFinalizedAt);

    $audit = AuditLog::query()
        ->where('domain', 'attendance')
        ->where('auditable_type', AttendanceRecord::class)
        ->where('auditable_id', $record->id)
        ->where('action', 'update_status')
        ->first();

    expect($audit)->not->toBeNull();

    expect($audit->old_values)
        ->toMatchArray([
            'status' => 'H',
            'note' => null,
        ]);

    expect($audit->new_values)
        ->toMatchArray([
            'status' => 'S',
            'note' => 'Demam',
        ]);
});
