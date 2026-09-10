<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\User;

class AcademicPeriodPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_mapel',
            'guru_piket',
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete models.
     */
    public function delete(User $user, AcademicPeriod $academicPeriod): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicPeriod $academicPeriod): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicPeriod $academicPeriod): bool
    {
        return false;
    }
}
