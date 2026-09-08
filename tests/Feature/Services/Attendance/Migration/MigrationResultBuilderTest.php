<?php

use App\Enums\AttendanceStatus;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Services\Attendance\Migration\MigrationResult;
use App\Services\Attendance\Migration\MigrationResultBuilder;
use App\Services\Attendance\Migration\MigrationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds a migration result with mapped attendance data', function () {
    $data = createAttendanceTestData();

    $period = Period::create([
        'number' => 4,
        'name' => 'Migration Test Period',
        'starts_at' => '07:00',
        'ends_at' => '08:00',
        'is_active' => true,
    ]);

    $data['schedule']->update([
        'period_id' => $period->id,
        'day_of_week' => 3,
    ]);

    $legacy = [
        'source' => [
            'file' => 'test.xlsx',
            'sheet' => 'Form Responses 1',
            'learning_group' => $data['learningGroup']->name,
            'rows' => 1,
            'student_count' => 2,
            'status_values_observed' => ['Hadir', 'Sakit'],
            'notes' => [],
        ],
        'rows' => [
            [
                'legacy_id' => 100001,
                'timestamp' => '2026-07-29T08:00:00.000',
                'email' => $data['teacher']->email,
                'teacher' => $data['teacher']->name,
                'date' => '2026-07-29',
                'jam_ke' => '7 - 8',
                'students' => [
                    $data['students'][0]->name => 'Hadir',
                    $data['students'][1]->name => 'Sakit',
                ],
            ],
        ],
    ];

    $result = app(MigrationResultBuilder::class)->build($legacy);

    expect($result)
        ->toBeInstanceOf(MigrationResult::class)
        ->source->toBe($legacy['source'])
        ->rows->toHaveCount(1);

    $row = $result->rows->first();

    expect($row->migration)
        ->toBe([
            'status' => MigrationStatus::Match->value,
            'note' => 'Mapped successfully.',
        ]);

    expect($row->attendanceSession)
        ->toMatchArray([
            'schedule_id' => $data['schedule']->id,
            'teacher_id_snapshot' => $data['teacher']->id,
            'subject_id_snapshot' => $data['subject']->id,
            'learning_group_id_snapshot' => $data['learningGroup']->id,
            'period_id_snapshot' => $period->id,
            'date' => '2026-07-29',
        ]);

    expect($row->attendanceRecords)
        ->toHaveCount(2)
        ->toContain([
            'student_id' => $data['students'][0]->id,
            'student_name' => $data['students'][0]->name,
            'status' => AttendanceStatus::Present->value,
            'note' => null,
        ])
        ->toContain([
            'student_id' => $data['students'][1]->id,
            'student_name' => $data['students'][1]->name,
            'status' => AttendanceStatus::Sick->value,
            'note' => null,
        ]);
});

it('builds a historical shift migration result', function () {
    $data = createAttendanceTestData();

    $referencePeriod = Period::create([
        'number' => 3,
        'name' => 'Reference Period',
        'starts_at' => '09:00',
        'ends_at' => '10:00',
        'is_active' => true,
    ]);

    $historicalPeriod = Period::create([
        'number' => 4,
        'name' => 'Historical Period',
        'starts_at' => '10:00',
        'ends_at' => '11:00',
        'is_active' => true,
    ]);

    $data['schedule']->update([
        'period_id' => $referencePeriod->id,
        'day_of_week' => 4,
    ]);

    $legacy = [
        'source' => [
            'file' => 'test.xlsx',
            'sheet' => 'Form Responses 1',
            'learning_group' => $data['learningGroup']->name,
            'rows' => 1,
            'student_count' => 2,
            'status_values_observed' => ['Hadir'],
            'notes' => [],
        ],
        'rows' => [
            [
                'legacy_id' => 462324,
                'timestamp' => '2026-07-29T08:00:00.000',
                'email' => $data['teacher']->email,
                'teacher' => $data['teacher']->name,
                'date' => '2026-07-29',
                'jam_ke' => '7 - 8',
                'students' => [
                    $data['students'][0]->name => 'Hadir',
                    $data['students'][1]->name => 'Hadir',
                ],
            ],
        ],
    ];

    $result = app(MigrationResultBuilder::class)->build($legacy, [
        462324 => [
            'status' => MigrationStatus::HistoricalShift,
            'schedule_id' => $data['schedule']->id,
            'period_id' => $historicalPeriod->id,
        ],
    ]);

    $row = $result->rows->first();

    expect($row->migration)
        ->toBe([
            'status' => MigrationStatus::HistoricalShift->value,
            'note' => "Historical shift. Reference schedule ID: {$data['schedule']->id}.",
        ]);

    expect($row->attendanceSession)
        ->toMatchArray([
            'schedule_id' => $data['schedule']->id,
            'teacher_id_snapshot' => $data['teacher']->id,
            'subject_id_snapshot' => $data['subject']->id,
            'learning_group_id_snapshot' => $data['learningGroup']->id,
            'period_id_snapshot' => $historicalPeriod->id,
            'date' => '2026-07-29',
        ]);

    expect($row->attendanceRecords)
        ->toHaveCount(2);
});

it('builds a skip migration result for an unmapped schedule', function () {
    $data = createAttendanceTestData();

    $period = Period::create([
        'number' => 4,
        'name' => 'Migration Test Period',
        'starts_at' => '10:00',
        'ends_at' => '11:00',
        'is_active' => true,
    ]);

    $data['schedule']->update([
        'period_id' => $period->id,
        'day_of_week' => 1,
    ]);

    $legacy = [
        'source' => [
            'file' => 'test.xlsx',
            'sheet' => 'Form Responses 1',
            'learning_group' => $data['learningGroup']->name,
            'rows' => 1,
            'student_count' => 2,
            'status_values_observed' => ['Hadir'],
            'notes' => [],
        ],
        'rows' => [
            [
                'legacy_id' => 100003,
                'timestamp' => '2026-07-29T08:00:00.000',
                'email' => $data['teacher']->email,
                'teacher' => $data['teacher']->name,
                'date' => '2026-07-29',
                'jam_ke' => '7 - 8',
                'students' => [
                    $data['students'][0]->name => 'Hadir',
                    $data['students'][1]->name => 'Hadir',
                ],
            ],
        ],
    ];

    $result = app(MigrationResultBuilder::class)->build($legacy);

    $row = $result->rows->first();

    expect($row->migration)
        ->toBe([
            'status' => MigrationStatus::Skip->value,
            'note' => 'Anomaly requires manual review.',
        ]);

    expect($row->attendanceSession)
        ->toMatchArray([
            'schedule_id' => null,
            'teacher_id_snapshot' => $data['teacher']->id,
            'subject_id_snapshot' => null,
            'learning_group_id_snapshot' => $data['learningGroup']->id,
            'period_id_snapshot' => $period->id,
            'date' => '2026-07-29',
        ]);

    expect($row->attendanceRecords)
        ->toHaveCount(2);
});

it('normalizes legacy learning group levels from numeric to roman numerals', function () {
    $data = createAttendanceTestData();

    $learningGroups = [
        '10 C' => 'X C',
        '11 C' => 'XI C',
        '12 C' => 'XII C',
    ];

    foreach (array_values($learningGroups) as $name) {
        LearningGroup::create([
            'academic_period_id' => $data['academicPeriod']->id,
            'name' => $name,
            'code' => str_replace(' ', '-', $name),
            'is_active' => true,
        ]);
    }

    $legacyId = 999001;

    foreach ($learningGroups as $index => $pair) {
        [$legacyName, $expectedName] = $pair;

        $legacy = [
            'source' => [
                'learning_group' => $legacyName,
            ],
            'rows' => [
                [
                    'legacy_id' => $legacyId++,
                    'timestamp' => '2026-07-21 07:00:00',
                    'email' => $data['teacher']->email,
                    'teacher' => $data['teacher']->name,
                    'date' => '2026-07-21',
                    'jam_ke' => '1 - 2',
                    'students' => [
                        $data['students'][0]->name => 'Hadir',
                    ],
                ],
            ],
        ];

        $result = app(MigrationResultBuilder::class)->build($legacy);

        expect($result->rows->first()->attendanceSession['learning_group_id_snapshot'])
            ->toBe(
                LearningGroup::query()
                    ->where('name', $expectedName)
                    ->value('id')
            );
    }
});
