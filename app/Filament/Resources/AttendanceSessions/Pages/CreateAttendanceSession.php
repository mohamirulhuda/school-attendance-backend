<?php

namespace App\Filament\Resources\AttendanceSessions\Pages;

use App\Filament\Resources\AttendanceSessions\AttendanceSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceSession extends CreateRecord
{
    protected static string $resource = AttendanceSessionResource::class;
}
