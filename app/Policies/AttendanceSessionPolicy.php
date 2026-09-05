<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\Teacher;
use App\Models\User;

class AttendanceSessionPolicy
{
    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $this->hasFullAttendanceAccess($user)
            || $this->isSessionTeacher($user, $attendanceSession);
    }

    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        return $this->hasFullAttendanceAccess($user)
            || $this->isSessionTeacher($user, $attendanceSession);
    }

    public function finalize(User $user, AttendanceSession $attendanceSession): bool
    {
        return $this->hasFullAttendanceAccess($user)
            || $this->isSessionTeacher($user, $attendanceSession);
    }

    private function hasFullAttendanceAccess(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'guru_piket',
        ]);
    }

    private function isSessionTeacher(
        User $user,
        AttendanceSession $attendanceSession,
    ): bool {
        if (!$user->hasRole('guru_mapel')) {
            return false;
        }

        $teacher = Teacher::query()
            ->where('email', $user->email)
            ->first();

        return $teacher?->id === $attendanceSession->teacher_id_snapshot;
    }
}
