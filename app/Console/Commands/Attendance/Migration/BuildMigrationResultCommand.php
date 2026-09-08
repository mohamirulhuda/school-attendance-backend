<?php

namespace App\Console\Commands\Attendance\Migration;

use App\Services\Attendance\Migration\MigrationResultBuilder;
use App\Services\Attendance\Migration\MigrationStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('attendance:migration:build-result')]
#[Description('Build migration result from legacy attendance JSON')]
class BuildMigrationResultCommand extends Command
{
    public function handle(MigrationResultBuilder $builder): int
    {
        $inputPath = storage_path(
            'app/private/attendance-migration/legacy/legacy_attendance_10_c_2026.json'
        );

        $outputPath = storage_path(
            'app/private/attendance-migration/results/migration_result_10_c_2026.json'
        );

        if (! File::exists($inputPath)) {
            $this->error("Legacy JSON not found: {$inputPath}");

            return self::FAILURE;
        }

        $legacy = json_decode(
            File::get($inputPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $overrides = [
            462324 => [
                'status' => MigrationStatus::HistoricalShift,
                'schedule_id' => 263,
                'period_id' => 4,
            ],
            462673 => [
                'status' => MigrationStatus::Skip,
            ],
            462674 => [
                'status' => MigrationStatus::Skip,
            ],
        ];

        $result = $builder->build($legacy, $overrides);

        File::ensureDirectoryExists(dirname($outputPath));

        File::put(
            $outputPath,
            json_encode(
                $result->toArray(),
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES,
            ),
        );

        $summary = $result->summary();

        $this->info('Migration result built successfully.');
        $this->line("Output: {$outputPath}");
        $this->line("Total: {$summary['total']}");
        $this->line("MATCH: {$summary['match']}");
        $this->line("HISTORICAL_SHIFT: {$summary['historical_shift']}");
        $this->line("SKIP: {$summary['skip']}");

        return self::SUCCESS;
    }
}
