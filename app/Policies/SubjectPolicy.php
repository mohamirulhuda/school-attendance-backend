<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->hasRole('admin')
            || (
                $subject->is_active
                && $user->hasAnyRole(['guru_mapel', 'guru_piket'])
            );
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Subject $subject): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subject $subject): bool
    {
        return false;
    }
}
