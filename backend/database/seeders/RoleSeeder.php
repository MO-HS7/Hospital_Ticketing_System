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
            // Phase 5: Order permissions
            'orders.create',
            'orders.view',
            'orders.process',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create roles (including Phase 5 staff roles)
        $roles = ['admin', 'patient', 'doctor', 'maintenance', 'reception', 'lab_technician', 'radiologist', 'pharmacist'];
        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Role-permission mappings
        // Note: Ticket visibility is primarily controlled by query logic in TicketController
        // tickets.view_all is only for admins who need to see ALL tickets
        $rolePermissions = [
            'admin' => ['tickets.create', 'tickets.view', 'tickets.view_all', 'tickets.update', 'tickets.delete', 'tickets.accept', 'tickets.complete', 'departments.manage', 'users.manage', 'reports.view', 'audit.view', 'orders.view', 'orders.process'],
            'patient' => ['tickets.create', 'tickets.view', 'orders.view'],
            'doctor' => ['tickets.create', 'tickets.view', 'tickets.accept', 'tickets.complete', 'orders.create', 'orders.view'],
            'maintenance' => ['tickets.view', 'tickets.accept', 'tickets.complete'],
            'reception' => ['tickets.create', 'tickets.view', 'orders.view'],
            'lab_technician' => ['orders.view', 'orders.process'],
            'radiologist' => ['orders.view', 'orders.process'],
            'pharmacist' => ['orders.view', 'orders.process'],
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
