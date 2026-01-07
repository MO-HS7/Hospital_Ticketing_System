<?php
// Quick script to assign patient role to the user
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// Find and fix all patient users
$patientEmails = [
    'patient@masar.local',
    'patient@hospital.com',
];

echo "=== Looking for specific users ===\n";
foreach ($patientEmails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "Found: {$user->email} (ID: {$user->id})\n";
        echo "  Current roles: " . ($user->roles->pluck('name')->join(', ') ?: '(none)') . "\n";
        
        if (!$user->hasRole('patient')) {
            $user->assignRole('patient');
            echo "  => Assigned 'patient' role\n";
        }
    } else {
        echo "NOT FOUND: {$email}\n";
    }
}

echo "\n=== All users with 'patient' in email ===\n";
$patientUsers = User::where('email', 'like', '%patient%')->get();
foreach ($patientUsers as $user) {
    echo "{$user->email} (ID: {$user->id}) - roles: " . ($user->roles->pluck('name')->join(', ') ?: '(none)') . "\n";
    if (!$user->hasRole('patient')) {
        $user->assignRole('patient');
        echo "  => Assigned 'patient' role\n";
    }
}

echo "\n=== Users with NO roles ===\n";
$noRoleUsers = User::doesntHave('roles')->get();
foreach ($noRoleUsers as $user) {
    echo "{$user->email} (ID: {$user->id}) - NO ROLES\n";
    // Auto-assign patient role to users without any role
    $user->assignRole('patient');
    echo "  => Assigned 'patient' role\n";
}

echo "\nDone!\n";
