<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasPublicId;

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
