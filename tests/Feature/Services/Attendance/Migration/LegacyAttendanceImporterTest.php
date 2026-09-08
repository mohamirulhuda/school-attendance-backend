<?php

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Services\Attendance\Migration\LegacyAttendanceImporter;
use App\Services\Attendance\Migration\MigrationResultBuilder;
use App\Services\Attendance\Migration\MigrationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\createAttendanceTestData;

uses(RefreshDatabase::class);

test('matched legacy attendance is imported as finalized historical attendance', function () {
    $data = createAttendanceTestData();
    $data['period']->update(['number' => 1]);
    $this->actingAs($data['user']);

    $builder = app(MigrationResultBuilder::class);
    $importer = app(LegacyAttendanceImporter::class);

    $legacy = [
        'source' => ['learning_group' => 'Test Class'],
        'rows' => [[
            'legacy_id' => 100001,
            'timestamp' => '2026-07-01T07:00:00',
            'email' => $data['teacher']->email,
            'teacher' => $data['teacher']->name,
            'date' => '2026-07-06',
            'jam_ke' => '1 - 2',
            'students' => [
                $data['students'][0]->name => 'Hadir',
                $data['students'][1]->name => 'Sakit',
            ],
        ]],
    ];

    $result = $builder->build($legacy);
    $summary = $importer->import($result, $data['user']->id);

    expect($result->summary())->toMatchArray([
        'total' => 1, 'match' => 1, 'historical_shift' => 0, 'skip' => 0,
    ]);

    expect($summary)->toMatchArray([
        'created' => 1, 'updated' => 0, 'skipped' => 0,
    ]);

    expect(AttendanceSession::query()->count())->toBe(1);

    $session = AttendanceSession::query()->first();

    expect($session->status)->toBe(AttendanceSessionStatus::Finalized);
    expect($session->schedule_id)->toBe($data['schedule']->id);

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->count())->toBe(2);

    expect(AuditLog::query()
        ->where('action', 'create_historical')
        ->where('auditable_id', $session->id)
        ->count())->toBe(1);

    expect(AuditLog::query()
        ->where('action', 'create_historical')
        ->where('auditable_id', $session->id)
        ->first()
        ->new_values['legacy_id'])->toBe(100001);

    expect($result->rows)->toHaveCount(1);
    expect($result->rows->first()->migration['status'])
        ->toBe(MigrationStatus::Match->value);
});

test('duplicate legacy id updates the existing historical attendance', function () {
    $data = createAttendanceTestData();
    $data['period']->update(['number' => 1]);
    $this->actingAs($data['user']);

    $builder = app(MigrationResultBuilder::class);
    $importer = app(LegacyAttendanceImporter::class);

    $legacyFirst = [
        'source' => ['learning_group' => 'Test Class'],
        'rows' => [[
            'legacy_id' => 100001,
            'timestamp' => '2026-07-06T07:00:00',
            'email' => $data['teacher']->email,
            'teacher' => $data['teacher']->name,
            'date' => '2026-07-06',
            'jam_ke' => '1 - 2',
            'students' => [
                $data['students'][0]->name => 'Hadir',
                $data['students'][1]->name => 'Sakit',
            ],
        ]],
    ];

    $firstSummary = $importer->import(
        $builder->build($legacyFirst),
        $data['user']->id,
    );

    expect($firstSummary)->toMatchArray([
        'created' => 1, 'updated' => 0, 'skipped' => 0,
    ]);

    $legacyLatest = [
        'source' => ['learning_group' => 'Test Class'],
        'rows' => [[
            'legacy_id' => 100001,
            'timestamp' => '2026-07-06T07:05:00',
            'email' => $data['teacher']->email,
            'teacher' => $data['teacher']->name,
            'date' => '2026-07-06',
            'jam_ke' => '1 - 2',
            'students' => [
                $data['students'][0]->name => 'Izin',
                $data['students'][1]->name => 'Alpha',
            ],
        ]],
    ];

    $latestSummary = $importer->import(
        $builder->build($legacyLatest),
        $data['user']->id,
    );

    expect($latestSummary)->toMatchArray([
        'created' => 0, 'updated' => 1, 'skipped' => 0,
    ]);

    expect(AttendanceSession::query()->count())->toBe(1);

    $session = AttendanceSession::query()->first();

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->count())->toBe(2);

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->where('student_id', $data['students'][0]->id)
        ->value('status'))->toBe(AttendanceStatus::Excused);

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->where('student_id', $data['students'][1]->id)
        ->value('status'))->toBe(AttendanceStatus::Absent);

    expect(AuditLog::query()
        ->where('action', 'create_historical')
        ->count())->toBe(1);

    expect(AuditLog::query()
        ->where('action', 'update_historical')
        ->count())->toBe(1);

    expect(AuditLog::query()
        ->where('action', 'update_historical')
        ->first()
        ->user_id)->toBe($data['user']->id);

    expect(AuditLog::query()
        ->where('action', 'create_historical')
        ->where('new_values->legacy_id', 100001)
        ->count())->toBe(1);
});

test('duplicate legacy id removes records missing from the latest historical attendance', function () {
    $data = createAttendanceTestData();
    $data['period']->update(['number' => 1]);
    $this->actingAs($data['user']);

    $builder = app(MigrationResultBuilder::class);
    $importer = app(LegacyAttendanceImporter::class);

    $legacyFirst = [
        'source' => ['learning_group' => 'Test Class'],
        'rows' => [[
            'legacy_id' => 100001,
            'timestamp' => '2026-07-06T07:00:00',
            'email' => $data['teacher']->email,
            'teacher' => $data['teacher']->name,
            'date' => '2026-07-06',
            'jam_ke' => '1 - 2',
            'students' => [
                $data['students'][0]->name => 'Hadir',
                $data['students'][1]->name => 'Sakit',
            ],
        ]],
    ];

    $importer->import(
        $builder->build($legacyFirst),
        $data['user']->id,
    );

    expect(AttendanceRecord::query()->count())->toBe(2);

    $legacyLatest = [
        'source' => ['learning_group' => 'Test Class'],
        'rows' => [[
            'legacy_id' => 100001,
            'timestamp' => '2026-07-06T07:05:00',
            'email' => $data['teacher']->email,
            'teacher' => $data['teacher']->name,
            'date' => '2026-07-06',
            'jam_ke' => '1 - 2',
            'students' => [
                $data['students'][0]->name => 'Izin',
            ],
        ]],
    ];

    $summary = $importer->import(
        $builder->build($legacyLatest),
        $data['user']->id,
    );

    expect($summary)->toMatchArray([
        'created' => 0, 'updated' => 1, 'skipped' => 0,
    ]);

    $session = AttendanceSession::query()->first();

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->count())->toBe(1);

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->where('student_id', $data['students'][0]->id)
        ->value('status'))->toBe(AttendanceStatus::Excused);

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->where('student_id', $data['students'][1]->id)
        ->exists())->toBeFalse();
});

test('historical shift legacy attendance is imported using the resolved migration result', function () {
    $data = createAttendanceTestData();
    $data['period']->update(['number' => 4]);
    $this->actingAs($data['user']);

    $builder = app(MigrationResultBuilder::class);
    $importer = app(LegacyAttendanceImporter::class);

    $legacy = [
        'source' => ['learning_group' => 'Test Class'],
        'rows' => [[
            'legacy_id' => 100002,
            'timestamp' => '2026-07-06T07:00:00',
            'email' => $data['teacher']->email,
            'teacher' => $data['teacher']->name,
            'date' => '2026-07-06',
            'jam_ke' => '7 - 8',
            'students' => [
                $data['students'][0]->name => 'Hadir',
                $data['students'][1]->name => 'Izin',
            ],
        ]],
    ];

    $result = $builder->build($legacy, [
        100002 => [
            'status' => MigrationStatus::HistoricalShift,
            'schedule_id' => $data['schedule']->id,
            'period_id' => $data['period']->id,
        ],
    ]);

    expect($result->summary())->toMatchArray([
        'total' => 1, 'match' => 0, 'historical_shift' => 1, 'skip' => 0,
    ]);

    expect($result->rows->first()->attendanceSession['schedule_id'])
        ->toBe($data['schedule']->id);

    expect($result->rows->first()->attendanceSession['period_id_snapshot'])
        ->toBe($data['period']->id);

    $summary = $importer->import($result, $data['user']->id);

    expect($summary)->toMatchArray([
        'created' => 1, 'updated' => 0, 'skipped' => 0,
    ]);

    $session = AttendanceSession::query()->first();

    expect($session->status)->toBe(AttendanceSessionStatus::Finalized);
    expect($session->schedule_id)->toBe($data['schedule']->id);
    expect($session->period_id_snapshot)->toBe($data['period']->id);

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->count())->toBe(2);

    expect(AuditLog::query()
        ->where('action', 'create_historical')
        ->where('auditable_id', $session->id)
        ->where('new_values->legacy_id', 100002)
        ->count())->toBe(1);
});
