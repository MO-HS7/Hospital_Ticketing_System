<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixDoctorsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fix Schema if missing
        if (!Schema::hasColumn('users', 'department_id')) {
            $this->command->info('Adding missing department_id column...');
            Schema::table('users', function (Blueprint $table) {
               $table->foreignUuid('department_id')->nullable()->after('id')->constrained('departments')->nullOnDelete();
            });
        } else {
            $this->command->info('Column department_id exists.');
        }

        // 2. Assign Doctors to Emergency (Using Spatie Scope)
        $emergencyId = 'a0c09fa0-96b4-4a8d-bb41-c3b496d76588';
        
        $users = User::role('doctor')->get();
        if ($users->isEmpty()) {
            $this->command->warn("No users with 'doctor' role found.");
        }

        foreach ($users as $user) {
            $user->department_id = $emergencyId;
            $user->save();
        }
        
        $this->command->info("Updated {$users->count()} doctors to Emergency department.");
        
        // 3. Verify
        $doctors = User::role('doctor')->where('department_id', $emergencyId)->count();
        $this->command->info("Verification: {$doctors} doctors now in Emergency.");
    }
}
