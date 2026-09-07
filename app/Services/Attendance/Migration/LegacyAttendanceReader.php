<?php

namespace App\Services\Attendance\Migration;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LegacyAttendanceReader
{
    public function read(string $filename): array
    {
        $path = 'attendance-migration/legacy/' . $filename;

        if (! Storage::exists($path)) {
            throw new RuntimeException(
                "Legacy attendance file not found: {$filename}"
            );
        }

        $contents = Storage::get($path);

        try {
            $data = json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                "Invalid legacy attendance JSON: {$filename}",
                previous: $exception
            );
        }

        if (! is_array($data)) {
            throw new RuntimeException(
                "Legacy attendance JSON must contain an object: {$filename}"
            );
        }

        return $data;
    }
}
