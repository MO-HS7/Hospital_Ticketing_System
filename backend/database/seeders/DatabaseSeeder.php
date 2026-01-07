<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset permission cache before seeding roles/permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->call([
            RoleSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            EncounterSeeder::class, // Phase 1
        ]);

        // Reset permission cache after seeding to ensure fresh cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
