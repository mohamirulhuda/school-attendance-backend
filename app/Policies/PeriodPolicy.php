<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'guru_mapel', 'guru_piket']);
    }

    public function view(User $u, Period $p): bool
    {
        return $u->hasAnyRole(['admin', 'guru_mapel', 'guru_piket']);
    }

    public function create(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function update(User $u, Period $p): bool
    {
        return $u->hasRole('admin');
    }

    public function delete(User $u, Period $p): bool
    {
        return false;
    }

    public function restore(User $u, Period $p): bool
    {
        return false;
    }

    public function forceDelete(User $u, Period $p): bool
    {
        return false;
    }
}
