<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru_mapel', 'guru_piket']);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('admin')
            || (
                $teacher->is_active
                && $user->hasAnyRole(['guru_mapel', 'guru_piket'])
            );
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return false;
    }
}
