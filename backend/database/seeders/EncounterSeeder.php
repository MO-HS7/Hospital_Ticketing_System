<?php

namespace Database\Seeders;

use App\Models\Encounter;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EncounterSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the patient user
        $patient = User::where('email', 'patient@hospital.com')->first();
        
        if (!$patient) {
            $this->command->warn('Patient user not found, skipping EncounterSeeder');
            return;
        }

        // Create 3 sample encounters for the patient
        
        // Encounter 1: Active encounter with one completed ticket
        $encounter1 = Encounter::create([
            'id' => Str::uuid(),
            'patient_id' => $patient->id,
            'status' => 'active',
            'source' => 'patient',
            'is_emergency' => false,
            'payment_status' => 'paid',
            'payment_reference' => 'PAY-' . strtoupper(Str::random(10)),
            'amount' => 150.00,
            'started_at' => now()->subDays(2),
        ]);

        // Link existing tickets to this encounter (if any exist)
        $existingTickets = Ticket::where('patient_id', $patient->id)
            ->whereNull('encounter_id')
            ->limit(1)
            ->get();
        
        foreach ($existingTickets as $ticket) {
            $ticket->update(['encounter_id' => $encounter1->id]);
        }

        // Encounter 2: Emergency encounter (payment waived)
        $encounter2 = Encounter::create([
            'id' => Str::uuid(),
            'patient_id' => $patient->id,
            'status' => 'completed',
            'source' => 'reception',
            'is_emergency' => true,
            'payment_status' => 'waived',
            'payment_reference' => null,
            'amount' => 0.00,
            'started_at' => now()->subDays(7),
            'ended_at' => now()->subDays(6),
        ]);

        // Encounter 3: Pending payment
        $encounter3 = Encounter::create([
            'id' => Str::uuid(),
            'patient_id' => $patient->id,
            'status' => 'active',
            'source' => 'patient',
            'is_emergency' => false,
            'payment_status' => 'pending',
            'payment_reference' => null,
            'amount' => 200.00,
            'started_at' => now()->subHours(3),
        ]);

        $this->command->info('Created 3 sample encounters for patient@hospital.com');
    }
}
