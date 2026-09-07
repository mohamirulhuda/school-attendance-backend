<?php

use App\Services\Attendance\Migration\LegacyAttendanceReader;
use Illuminate\Support\Facades\Storage;

it('reads a legacy attendance json file', function (): void {
    Storage::fake();

    Storage::put(
        'attendance-migration/legacy/test.json',
        json_encode([
            'source' => [
                'file' => 'test.xlsx',
                'learning_group' => '10 C',
            ],
            'rows' => [
                [
                    'legacy_id' => 123,
                ],
            ],
        ])
    );

    $result = app(LegacyAttendanceReader::class)->read('test.json');

    expect($result['source']['file'])->toBe('test.xlsx')
        ->and($result['source']['learning_group'])->toBe('10 C')
        ->and($result['rows'][0]['legacy_id'])->toBe(123);
});

it('rejects a missing legacy attendance file', function (): void {
    Storage::fake();

    app(LegacyAttendanceReader::class)->read('missing.json');
})->throws(
    RuntimeException::class,
    'Legacy attendance file not found: missing.json'
);

it('rejects invalid json', function (): void {
    Storage::fake();

    Storage::put(
        'attendance-migration/legacy/invalid.json',
        '{ invalid json'
    );

    app(LegacyAttendanceReader::class)->read('invalid.json');
})->throws(
    RuntimeException::class,
    'Invalid legacy attendance JSON: invalid.json'
);
