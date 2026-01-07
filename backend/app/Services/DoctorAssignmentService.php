<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DoctorAssignmentService
{
    /**
     * Auto-assign a doctor from the specified department based on workload.
     *
     * @param string $departmentId
     * @return User|null The assigned doctor or null if none available
     */
    public function assignDoctor(string $departmentId): ?User
    {
        // Find all active doctors in the department
        $doctors = User::where('department_id', $departmentId)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })
            ->where('is_active', true)
            ->get();

        if ($doctors->isEmpty()) {
            return null;
        }

        // Calculate workload for each doctor (count of in_progress + pending assigned tickets)
        $doctorWorkload = [];
        
        foreach ($doctors as $doctor) {
            $workload = DB::table('tickets')
                ->where('assigned_to', $doctor->id)
                ->whereIn('status', ['in_progress', 'pending', 'assigned'])
                ->count();
            
            $doctorWorkload[$doctor->id] = [
                'doctor' => $doctor,
                'workload' => $workload,
                'created_at' => $doctor->created_at
            ];
        }

        // Sort by workload (ascending), then by created_at (earliest first)
        usort($doctorWorkload, function ($a, $b) {
            if ($a['workload'] === $b['workload']) {
                return $a['created_at'] <=> $b['created_at'];
            }
            return $a['workload'] <=> $b['workload'];
        });

        // Return the doctor with the least workload
        return $doctorWorkload[0]['doctor'] ?? null;
    }
}
