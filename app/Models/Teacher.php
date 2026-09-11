<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'nip',
        'name',
        'nickname',
        'gender',
        'title_prefix',
        'title_suffix',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
