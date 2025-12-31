<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Analyze symptoms and suggest department.
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = strtolower($request->message);

        // Simple keyword-based analysis (placeholder for AI integration)
        $suggestions = $this->analyzeSymptoms($message);

        return response()->json([
            'suggestions' => $suggestions,
            'departments' => Department::whereIn('id', collect($suggestions)->pluck('department_id'))->get(),
            'disclaimer' => 'This is not a medical diagnosis. Please consult a doctor for medical advice.',
        ]);
    }

    /**
     * Simple symptom analysis (placeholder).
     */
    private function analyzeSymptoms(string $message): array
    {
        $keywords = [
            'heart' => ['department_id' => 1, 'name' => 'Cardiology', 'confidence' => 0.8],
            'chest pain' => ['department_id' => 1, 'name' => 'Cardiology', 'confidence' => 0.9],
            'bone' => ['department_id' => 2, 'name' => 'Orthopedics', 'confidence' => 0.8],
            'fracture' => ['department_id' => 2, 'name' => 'Orthopedics', 'confidence' => 0.9],
            'joint' => ['department_id' => 2, 'name' => 'Orthopedics', 'confidence' => 0.7],
            'headache' => ['department_id' => 3, 'name' => 'Neurology', 'confidence' => 0.7],
            'brain' => ['department_id' => 3, 'name' => 'Neurology', 'confidence' => 0.8],
            'child' => ['department_id' => 4, 'name' => 'Pediatrics', 'confidence' => 0.8],
            'baby' => ['department_id' => 4, 'name' => 'Pediatrics', 'confidence' => 0.9],
            'skin' => ['department_id' => 5, 'name' => 'Dermatology', 'confidence' => 0.8],
            'rash' => ['department_id' => 5, 'name' => 'Dermatology', 'confidence' => 0.9],
            'eye' => ['department_id' => 6, 'name' => 'Ophthalmology', 'confidence' => 0.9],
            'vision' => ['department_id' => 6, 'name' => 'Ophthalmology', 'confidence' => 0.8],
            'ear' => ['department_id' => 7, 'name' => 'ENT', 'confidence' => 0.8],
            'throat' => ['department_id' => 7, 'name' => 'ENT', 'confidence' => 0.8],
            'nose' => ['department_id' => 7, 'name' => 'ENT', 'confidence' => 0.8],
        ];

        $suggestions = [];
        foreach ($keywords as $keyword => $suggestion) {
            if (str_contains($message, $keyword)) {
                $suggestions[] = $suggestion;
            }
        }

        // Default to General Medicine if no match
        if (empty($suggestions)) {
            $suggestions[] = ['department_id' => 8, 'name' => 'General Medicine', 'confidence' => 0.5];
        }

        return $suggestions;
    }
}
