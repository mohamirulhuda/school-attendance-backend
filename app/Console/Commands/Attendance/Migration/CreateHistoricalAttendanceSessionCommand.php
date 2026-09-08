<?php

namespace App\Console\Commands\Attendance\Migration;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-historical-attendance-session {legacy_id}')]
#[Description('Create a historical attendance session from migration data')]
class CreateHistoricalAttendanceSessionCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $legacyId = $this->argument('legacy_id');

        $this->info("Historical attendance session command received: {$legacyId}");

        return self::SUCCESS;
    }
}
