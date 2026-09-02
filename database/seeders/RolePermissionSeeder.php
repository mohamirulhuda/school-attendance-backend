<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_attendance',
            'start_attendance',
            'update_attendance',
            'finalize_attendance',
            'view_attendance_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $attendancePermissions = Permission::whereIn('name', $permissions)->get();

        $roles = [
            'admin',
            'guru_mapel',
            'guru_piket',
        ];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName);

            $role->syncPermissions($attendancePermissions);
        }
    }
}
