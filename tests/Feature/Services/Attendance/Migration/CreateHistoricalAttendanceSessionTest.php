<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Services\Attendance\Migration\CreateHistoricalAttendanceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('creates a finalized historical attendance session', function () {
    $data = createAttendanceTestData();

    $session = app(CreateHistoricalAttendanceSession::class)->execute([
        'schedule_id' => $data['schedule']->id,
        'teacher_id_snapshot' => $data['teacher']->id,
        'subject_id_snapshot' => $data['subject']->id,
        'learning_group_id_snapshot' => $data['learningGroup']->id,
        'period_id_snapshot' => $data['period']->id,
        'date' => '2026-07-01',
        'attendance_records' => [
            [
                'student_id' => $data['students'][0]->id,
                'status' => 'H',
                'note' => null,
            ],
            [
                'student_id' => $data['students'][1]->id,
                'status' => 'S',
                'note' => 'Demam',
            ],
        ],
        'legacy_id' => 123456,
    ], $data['user']->id);

    expect($session)
        ->toBeInstanceOf(AttendanceSession::class)
        ->status->value->toBe('finalized')
        ->schedule_id->toBe($data['schedule']->id)
        ->teacher_id_snapshot->toBe($data['teacher']->id)
        ->subject_id_snapshot->toBe($data['subject']->id)
        ->learning_group_id_snapshot->toBe($data['learningGroup']->id)
        ->period_id_snapshot->toBe($data['period']->id)
        ->date->toDateString()->toBe('2026-07-01');

    expect($session->attendanceRecords)->toHaveCount(2);

    expect(
        $session->attendanceRecords
            ->firstWhere('student_id', $data['students'][0]->id)
            ->status->value
    )->toBe('H');

    expect(
        $session->attendanceRecords
            ->firstWhere('student_id', $data['students'][1]->id)
            ->status->value
    )->toBe('S');
});

it('creates historical attendance records with provided snapshot data', function () {
    $data = createAttendanceTestData();

    $session = app(CreateHistoricalAttendanceSession::class)->execute([
        'schedule_id' => $data['schedule']->id,
        'teacher_id_snapshot' => $data['teacher']->id,
        'subject_id_snapshot' => $data['subject']->id,
        'learning_group_id_snapshot' => $data['learningGroup']->id,
        'period_id_snapshot' => $data['period']->id,
        'date' => '2026-07-02',
        'attendance_records' => [
            [
                'student_id' => $data['students'][0]->id,
                'status' => 'I',
                'note' => 'Acara keluarga',
            ],
            [
                'student_id' => $data['students'][1]->id,
                'status' => 'A',
                'note' => null,
            ],
        ],
        'legacy_id' => 123457,
    ], $data['user']->id);

    $records = AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->get()
        ->keyBy('student_id');

    expect($records)->toHaveCount(2);

    expect($records[$data['students'][0]->id]->status->value)->toBe('I');
    expect($records[$data['students'][0]->id]->note)->toBe('Acara keluarga');

    expect($records[$data['students'][1]->id]->status->value)->toBe('A');
    expect($records[$data['students'][1]->id]->note)->toBeNull();
});

it('rolls back creation when a record fails', function () {
    $data = createAttendanceTestData();

    try {
        app(CreateHistoricalAttendanceSession::class)->execute([
            'schedule_id' => $data['schedule']->id,
            'teacher_id_snapshot' => $data['teacher']->id,
            'subject_id_snapshot' => $data['subject']->id,
            'learning_group_id_snapshot' => $data['learningGroup']->id,
            'period_id_snapshot' => $data['period']->id,
            'date' => '2026-07-03',
            'attendance_records' => [
                [
                    'student_id' => $data['students'][0]->id,
                    'status' => 'H',
                    'note' => null,
                ],
                [
                    'student_id' => 999999,
                    'status' => 'H',
                    'note' => null,
                ],
            ],
            'legacy_id' => 123458,
        ], $data['user']->id);
    } catch (\Throwable $exception) {
        expect($exception)->toBeInstanceOf(\Throwable::class);
    }

    expect(
        AttendanceSession::query()
            ->where('date', '2026-07-03')
            ->exists()
    )->toBeFalse();

    expect(
        AttendanceRecord::query()->exists()
    )->toBeFalse();
});
