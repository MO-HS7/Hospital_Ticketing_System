<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'tickets.create',
            'tickets.view',
            'tickets.view_all',
            'tickets.update',
            'tickets.delete',
            'tickets.accept',
            'tickets.complete',
            'departments.manage',
            'users.manage',
            'reports.view',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create roles
        $roles = ['admin', 'patient', 'doctor', 'maintenance', 'reception'];
        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Role-permission mappings
        $rolePermissions = [
            'admin' => ['tickets.create', 'tickets.view', 'tickets.view_all', 'tickets.update', 'tickets.delete', 'tickets.accept', 'tickets.complete', 'departments.manage', 'users.manage', 'reports.view', 'audit.view'],
            'patient' => ['tickets.create', 'tickets.view'],
            'doctor' => ['tickets.view', 'tickets.view_all', 'tickets.accept', 'tickets.complete'],
            'maintenance' => ['tickets.view', 'tickets.view_all', 'tickets.accept', 'tickets.complete'],
            'reception' => ['tickets.create', 'tickets.view', 'tickets.view_all'],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if ($role) {
                foreach ($perms as $permName) {
                    $perm = DB::table('permissions')->where('name', $permName)->first();
                    if ($perm) {
                        DB::table('role_has_permissions')->insertOrIgnore([
                            'permission_id' => $perm->id,
                            'role_id' => $role->id,
                        ]);
                    }
                }
            }
        }
    }
}
