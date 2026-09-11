<?php

namespace App\Policies;

use App\Models\LearningGroupStudent;
use App\Models\User;

class LearningGroupStudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru_mapel', 'guru_piket']);
    }

    public function view(User $user, LearningGroupStudent $learningGroupStudent): bool
    {
        return $user->hasAnyRole(['admin', 'guru_mapel', 'guru_piket']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, LearningGroupStudent $learningGroupStudent): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, LearningGroupStudent $learningGroupStudent): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, LearningGroupStudent $learningGroupStudent): bool
    {
        return false;
    }

    public function forceDelete(User $user, LearningGroupStudent $learningGroupStudent): bool
    {
        return false;
    }
}
