<?php

namespace App\Enums;

enum AttendanceSessionStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
}
