<?php

namespace Database\Seeders;

use App\Models\Department;
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
        // Get departments
        $cardiology = Department::where('slug', 'cardiology')->first();
        $orthopedics = Department::where('slug', 'orthopedics')->first();
        $neurology = Department::where('slug', 'neurology')->first();
        $pediatrics = Department::where('slug', 'pediatrics')->first();
        $emergency = Department::where('slug', 'emergency')->first();
        $itMaintenance = Department::where('slug', 'it-maintenance')->first();
        $dermatology = Department::where('slug', 'dermatology')->first();
        $ophthalmology = Department::where('slug', 'ophthalmology')->first();

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

        // Doctors - one per major department
        $doctors = [
            ['email' => 'doctor@hospital.com', 'name' => 'Dr. Ahmed Al-Rashid', 'dept' => $cardiology],
            ['email' => 'dr.sarah@hospital.com', 'name' => 'Dr. Sarah Hassan', 'dept' => $orthopedics],
            ['email' => 'dr.omar@hospital.com', 'name' => 'Dr. Omar Khalid', 'dept' => $neurology],
            ['email' => 'dr.layla@hospital.com', 'name' => 'Dr. Layla Mahmoud', 'dept' => $pediatrics],
            ['email' => 'dr.yusuf@hospital.com', 'name' => 'Dr. Yusuf Ali', 'dept' => $emergency],
            ['email' => 'dr.fatima@hospital.com', 'name' => 'Dr. Fatima Noor', 'dept' => $dermatology],
            ['email' => 'dr.karim@hospital.com', 'name' => 'Dr. Karim Jamal', 'dept' => $ophthalmology],
        ];

        foreach ($doctors as $doctorData) {
            $doctor = User::firstOrCreate(
                ['email' => $doctorData['email']],
                [
                    'name' => $doctorData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if ($doctorData['dept']) {
                $doctor->update(['department_id' => $doctorData['dept']->id]);
            }
            $doctor->syncRoles(['doctor']);
        }

        // Maintenance
        $maintenance = User::firstOrCreate(
            ['email' => 'maintenance@hospital.com'],
            [
                'name' => 'Maintenance Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($itMaintenance) {
            $maintenance->update(['department_id' => $itMaintenance->id]);
        }
        $maintenance->syncRoles(['maintenance']);

        // Reception
        $reception = User::firstOrCreate(
            ['email' => 'reception@hospital.com'],
            [
                'name' => 'Reception Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($emergency) {
            $reception->update(['department_id' => $emergency->id]);
        }
        $reception->syncRoles(['reception']);

        // Phase 5: Lab Technician
        $laboratory = Department::where('slug', 'laboratory')->first();
        $lab = User::firstOrCreate(
            ['email' => 'lab@hospital.com'],
            [
                'name' => 'Lab Technician',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($laboratory) {
            $lab->update(['department_id' => $laboratory->id]);
        }
        $lab->syncRoles(['lab_technician']);

        // Phase 5: Radiologist
        $radiologyDept = Department::where('slug', 'radiology')->first();
        $radiologist = User::firstOrCreate(
            ['email' => 'radiology@hospital.com'],
            [
                'name' => 'Radiologist Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($radiologyDept) {
            $radiologist->update(['department_id' => $radiologyDept->id]);
        }
        $radiologist->syncRoles(['radiologist']);

        // Phase 5: Pharmacist
        $pharmacyDept = Department::where('slug', 'pharmacy')->first();
        $pharmacist = User::firstOrCreate(
            ['email' => 'pharmacy@hospital.com'],
            [
                'name' => 'Pharmacist Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($pharmacyDept) {
            $pharmacist->update(['department_id' => $pharmacyDept->id]);
        }
        $pharmacist->syncRoles(['pharmacist']);
    }
}

