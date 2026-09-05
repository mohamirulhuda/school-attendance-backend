<?php

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceSessionStatus;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Attendance\StartAttendanceSession;
use App\Services\Attendance\UpdateAttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('updates attendance status and records an audit log', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $record = $session->attendanceRecords()->first();

    $updatedRecord = app(UpdateAttendanceStatus::class)->execute(
        $record->id,
        AttendanceStatus::Sick,
        'Demam',
    );

    expect($updatedRecord->status)
        ->toBe(AttendanceStatus::Sick);

    expect($updatedRecord->note)
        ->toBe('Demam');

    $record->refresh();

    expect($record->status)
        ->toBe(AttendanceStatus::Sick);

    expect($record->note)
        ->toBe('Demam');

    $audit = AuditLog::query()
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
//
//it('rejects attendance update when the session is finalized', function () {
//    $data = createAttendanceTestData();
//
//    $this->actingAs($data['user']);
//
//    $session = app(StartAttendanceSession::class)->execute(
//        $data['schedule']->id,
//        Carbon::parse('2026-07-01'),
//    );
//
//    $record = $session->attendanceRecords()->first();
//
//    $session->update([
//        'status' => AttendanceSessionStatus::Finalized,
//        'finalized_by' => $data['user']->id,
//        'finalized_at' => now(),
//    ]);
//
//    expect(fn () => app(UpdateAttendanceStatus::class)->execute(
//        $record->id,
//        AttendanceStatus::Sick,
//        'Demam',
//    ))->toThrow(ValidationException::class);
//
//    $record->refresh();
//
//    expect($record->status)
//        ->toBe(AttendanceStatus::Present);
//
//    expect($record->note)
//        ->toBeNull();
//
//    expect(
//        AuditLog::query()
//            ->where('auditable_type', AttendanceRecord::class)
//            ->where('auditable_id', $record->id)
//            ->where('action', 'update_status')
//            ->count()
//    )->toBe(0);
//});

it('rejects update when the attendance record does not exist', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    expect(fn () => app(UpdateAttendanceStatus::class)->execute(
        999999,
        AttendanceStatus::Sick,
        'Demam',
    ))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(AuditLog::query()
        ->where('domain', 'attendance')
        ->where('action', 'update_status')
        ->count()
    )->toBe(0);
});

it('rejects update by a non-owner guru_mapel', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(StartAttendanceSession::class)->execute(
        $data['schedule']->id,
        Carbon::parse('2026-07-01'),
    );

    $record = $session->attendanceRecords()->first();

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

    expect(fn () => app(UpdateAttendanceStatus::class)->execute(
        $record->id,
        AttendanceStatus::Sick,
    ))->toThrow(AuthorizationException::class);

    expect($record->refresh()->status)
        ->toBe(AttendanceStatus::Present);

    expect(
        AuditLog::query()
            ->where('domain', 'attendance')
            ->where('action', 'update_status')
            ->count()
    )->toBe(0);
});
