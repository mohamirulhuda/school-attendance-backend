<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    public function view(User $user, Enrollment $enrollment): bool
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

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }

    public function restore(User $user, Enrollment $enrollment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }
}
