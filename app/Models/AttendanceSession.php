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

    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'teacher_id_snapshot',
        'subject_id_snapshot',
        'learning_group_id_snapshot',
        'period_id_snapshot',
        'date',
        'status',
        'opened_at',
        'finalized_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AttendanceSessionStatus::class,
            'opened_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacherSnapshot(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id_snapshot');
    }

    public function subjectSnapshot(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id_snapshot');
    }

    public function learningGroupSnapshot(): BelongsTo
    {
        return $this->belongsTo(LearningGroup::class, 'learning_group_id_snapshot');
    }

    public function periodSnapshot(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id_snapshot');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
