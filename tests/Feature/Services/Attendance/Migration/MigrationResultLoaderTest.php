<?php

use App\Services\Attendance\Migration\MigrationResult;
use App\Services\Attendance\Migration\MigrationResultLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads a migration result from json artifact', function () {
    $path = storage_path(
        'app/private/attendance-migration/results/migration_result_10_c_2026.json'
    );

    $result = app(MigrationResultLoader::class)->load($path);

    expect($result)
        ->toBeInstanceOf(MigrationResult::class);

    expect($result->source)
        ->toMatchArray([
            'file' => 'ABSENSI 10 C (Jawaban).xlsx',
            'sheet' => 'Form Responses 1',
            'learning_group' => '10 C',
            'rows' => 132,
            'student_count' => 32,
        ]);

    expect($result->rows)
        ->toHaveCount(132);

    expect($result->summary())
        ->toMatchArray([
            'total' => 132,
            'match' => 129,
            'historical_shift' => 1,
            'skip' => 2,
        ]);

    $firstRow = $result->rows->first();

    expect($firstRow->legacyId)
        ->toBe(462242);

    expect($firstRow->migration)
        ->toMatchArray([
            'status' => 'match',
            'note' => 'Mapped successfully.',
        ]);

    expect($firstRow->attendanceSession)
        ->toMatchArray([
            'schedule_id' => 150,
            'teacher_id_snapshot' => 25,
            'subject_id_snapshot' => 6,
            'learning_group_id_snapshot' => 17,
            'period_id_snapshot' => 2,
            'date' => '2026-07-21',
        ]);
});

it('rejects a missing migration result file', function () {
    $path = storage_path(
        'app/private/attendance-migration/results/missing.json'
    );

    expect(fn () => app(MigrationResultLoader::class)->load($path))
        ->toThrow(\RuntimeException::class);
});

it('rejects invalid migration result json', function () {
    $path = storage_path(
        'app/private/attendance-migration/results/invalid.json'
    );

    file_put_contents($path, '{invalid-json');

    try {
        expect(fn () => app(MigrationResultLoader::class)->load($path))
            ->toThrow(\JsonException::class);
    } finally {
        @unlink($path);
    }
});
