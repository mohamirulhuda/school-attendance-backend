<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'H';
    case Sick = 'S';
    case Excused = 'I';
    case Absent = 'A';
}
