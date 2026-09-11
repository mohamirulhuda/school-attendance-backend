<?php

namespace App\Policies;

use App\Models\LearningGroup;
use App\Models\User;

class LearningGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    public function view(User $user, LearningGroup $learningGroup): bool
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

    public function update(User $user, LearningGroup $learningGroup): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, LearningGroup $learningGroup): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, LearningGroup $learningGroup): bool
    {
        return false;
    }

    public function forceDelete(User $user, LearningGroup $learningGroup): bool
    {
        return false;
    }
}
