<?php

namespace App\Services\Attendance\Migration;

enum MigrationStatus: string
{
    case Match = 'match';
    case HistoricalShift = 'historical_shift';
    case Skip = 'skip';
}
