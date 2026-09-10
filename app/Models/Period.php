<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    use HasPublicId;

    protected $fillable = ['number', 'name', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime:H:i', 'ends_at' => 'datetime:H:i', 'is_active' => 'boolean'];
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
