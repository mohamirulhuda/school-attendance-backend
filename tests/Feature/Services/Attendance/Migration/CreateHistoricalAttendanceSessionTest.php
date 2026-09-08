<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AuditLog;
use App\Services\Attendance\Migration\CreateHistoricalAttendanceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a historical attendance session as finalized', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(CreateHistoricalAttendanceSession::class)->execute([
        'schedule_id' => $data['schedule']->id,
        'teacher_id_snapshot' => $data['teacher']->id,
        'subject_id_snapshot' => $data['subject']->id,
        'learning_group_id_snapshot' => $data['learningGroup']->id,
        'period_id_snapshot' => $data['period']->id,
        'date' => '2026-07-29',
        'legacy_id' => 462324,
        'attendance_records' => [
            [
                'student_id' => $data['students'][0]->id,
                'status' => AttendanceStatus::Present,
                'note' => null,
            ],
        ],
    ]);

    expect($session->status)
        ->toBe(AttendanceSessionStatus::Finalized)
        ->and($session->finalized_by)
        ->toBe($data['user']->id)
        ->and($session->finalized_at)
        ->not->toBeNull();

    expect(AuditLog::query()
        ->where('auditable_type', $session::class)
        ->where('auditable_id', $session->id)
        ->where('action', 'create_historical')
        ->exists())
        ->toBeTrue();
});

it('creates historical attendance records with the provided snapshot data', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    $session = app(CreateHistoricalAttendanceSession::class)->execute([
        'schedule_id' => $data['schedule']->id,
        'teacher_id_snapshot' => $data['teacher']->id,
        'subject_id_snapshot' => $data['subject']->id,
        'learning_group_id_snapshot' => $data['learningGroup']->id,
        'period_id_snapshot' => $data['period']->id,
        'date' => '2026-07-29',
        'legacy_id' => 462324,
        'attendance_records' => [
            [
                'student_id' => $data['students'][0]->id,
                'status' => AttendanceStatus::Present,
                'note' => null,
            ],
            [
                'student_id' => $data['students'][1]->id,
                'status' => AttendanceStatus::Sick,
                'note' => 'Legacy sick note',
            ],
        ],
    ]);

    expect($session->schedule_id)
        ->toBe($data['schedule']->id)
        ->and($session->teacher_id_snapshot)
        ->toBe($data['teacher']->id)
        ->and($session->subject_id_snapshot)
        ->toBe($data['subject']->id)
        ->and($session->learning_group_id_snapshot)
        ->toBe($data['learningGroup']->id)
        ->and($session->period_id_snapshot)
        ->toBe($data['period']->id)
        ->and($session->date->toDateString())
        ->toBe('2026-07-29');

    expect($session->attendanceRecords)
        ->toHaveCount(2);

    expect($session->attendanceRecords->firstWhere(
        'student_id',
        $data['students'][0]->id
    ))
        ->status->toBe(AttendanceStatus::Present)
        ->note->toBeNull();

    expect($session->attendanceRecords->firstWhere(
        'student_id',
        $data['students'][1]->id
    ))
        ->status->toBe(AttendanceStatus::Sick)
        ->note->toBe('Legacy sick note');
});

it('rolls back the historical attendance creation when a record fails', function () {
    $data = createAttendanceTestData();

    $this->actingAs($data['user']);

    expect(fn () => app(CreateHistoricalAttendanceSession::class)->execute([
        'schedule_id' => $data['schedule']->id,
        'teacher_id_snapshot' => $data['teacher']->id,
        'subject_id_snapshot' => $data['subject']->id,
        'learning_group_id_snapshot' => $data['learningGroup']->id,
        'period_id_snapshot' => $data['period']->id,
        'date' => '2026-07-29',
        'legacy_id' => 462324,
        'attendance_records' => [
            [
                'student_id' => $data['students'][0]->id,
                'status' => AttendanceStatus::Present,
                'note' => null,
            ],
            [
                'student_id' => 999999,
                'status' => AttendanceStatus::Sick,
                'note' => 'This record must fail.',
            ],
        ],
    ]))->toThrow(\Illuminate\Database\QueryException::class);

    expect(\App\Models\AttendanceSession::query()->count())
        ->toBe(0);

    expect(\App\Models\AttendanceRecord::query()->count())
        ->toBe(0);

    expect(AuditLog::query()
        ->where('domain', 'attendance')
        ->count()
    )->toBe(0);
});
