<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 *
 * @method static void creating(Closure $callback)
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model) {
            $model->public_id ??= (string) Str::ulid();
        });
    }
}
