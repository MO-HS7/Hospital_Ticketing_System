<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DistributeDoctorsSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();
        $doctors = User::role('doctor')->get();
        
        $this->command->info("Found {$departments->count()} departments and {$doctors->count()} existing doctors.");

        // 1. Distribute existing doctors to the first N departments
        $deptIndex = 0;
        foreach ($doctors as $doctor) {
            if (isset($departments[$deptIndex])) {
                $doctor->department_id = $departments[$deptIndex]->id;
                $doctor->save();
                $this->command->info("Assigned Dr. {$doctor->name} to {$departments[$deptIndex]->name}");
                $deptIndex++;
            }
        }

        // 2. Create new doctors for remaining departments
        for ($i = $deptIndex; $i < $departments->count(); $i++) {
            $dept = $departments[$i];
            
            // Check if dept already has doctor (unlikely if we just did linear assign, but good practice)
            if (User::role('doctor')->where('department_id', $dept->id)->exists()) {
                continue;
            }

            $email = "doctor." . Str::slug($dept->name_en) . "@hospital.com";
            
            // Avoid duplicates
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $doctor = User::create([
                'name' => "Dr. {$dept->name_en}",
                'email' => $email,
                'password' => Hash::make('password'),
                'is_active' => true,
                'department_id' => $dept->id,
                'email_verified_at' => now(),
            ]);
            
            $doctor->assignRole('doctor');
            
            $this->command->info("Created new doctor for {$dept->name_en}: {$email}");
        }
        
        $totalDoctors = User::role('doctor')->count();
        $this->command->info("Seeding complete. Total doctors: {$totalDoctors}. Every department should now have coverage.");
    }
}
