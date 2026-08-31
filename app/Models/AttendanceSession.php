<?php

namespace App\Models;

use App\Enums\AttendanceSessionStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasPublicId;

    protected $fillable = [
        'schedule_id',
        'date',
        'status',
        'opened_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AttendanceSessionStatus::class,
            'opened_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
