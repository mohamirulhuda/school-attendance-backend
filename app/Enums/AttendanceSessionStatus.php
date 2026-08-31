<?php

namespace App\Enums;

enum AttendanceSessionStatus: string
{
    case Open = 'open';
    case Submitted = 'submitted';
}
