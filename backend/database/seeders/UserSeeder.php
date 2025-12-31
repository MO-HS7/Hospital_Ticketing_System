<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@hospital.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Patient
        $patient = User::firstOrCreate(
            ['email' => 'patient@hospital.com'],
            [
                'name' => 'Patient User',
                'phone' => '+966500000001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $patient->assignRole('patient');

        // Doctor
        $doctor = User::firstOrCreate(
            ['email' => 'doctor@hospital.com'],
            [
                'name' => 'Dr. Ahmed',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $doctor->assignRole('doctor');

        // Maintenance
        $maintenance = User::firstOrCreate(
            ['email' => 'maintenance@hospital.com'],
            [
                'name' => 'Maintenance Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $maintenance->assignRole('maintenance');

        // Reception
        $reception = User::firstOrCreate(
            ['email' => 'reception@hospital.com'],
            [
                'name' => 'Reception Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $reception->assignRole('reception');
    }
}
