<?php

namespace App\Console\Commands\Attendance\Migration;

use App\Services\Attendance\Migration\LegacyAttendanceImporter;
use App\Services\Attendance\Migration\MigrationResultLoader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:migration:import')]
#[Description('Import legacy attendance from migration result')]
class ImportLegacyAttendanceCommand extends Command
{
    public function handle(
        MigrationResultLoader $loader,
        LegacyAttendanceImporter $importer,
    ): int {
        $resultPath = storage_path(
            'app/private/attendance-migration/results/migration_result_10_c_2026.json'
        );

        if (! file_exists($resultPath)) {
            $this->error("Migration result not found: {$resultPath}");

            return self::FAILURE;
        }

        $result = $loader->load($resultPath);

        $summary = $result->summary();

        $this->line("Migration Result: {$resultPath}");
        $this->line("Total: {$summary['total']}");
        $this->line("MATCH: {$summary['match']}");
        $this->line("HISTORICAL_SHIFT: {$summary['historical_shift']}");
        $this->line("SKIP: {$summary['skip']}");

        $result = $importer->import(
            $result,
            1,
        );

        $this->newLine();

        $this->info('Legacy attendance import completed.');
        $this->line("Created: {$result['created']}");
        $this->line("Updated: {$result['updated']}");
        $this->line("Skipped: {$result['skipped']}");

        return self::SUCCESS;
    }
}
