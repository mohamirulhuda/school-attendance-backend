<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasPublicId;

    protected $fillable = ['academic_period_id', 'student_id', 'classroom_id', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
