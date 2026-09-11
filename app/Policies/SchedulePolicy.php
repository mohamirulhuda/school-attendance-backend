<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return false;
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return false;
    }
}
