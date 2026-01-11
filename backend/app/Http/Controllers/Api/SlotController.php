<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotAvailabilityService;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    protected SlotAvailabilityService $slotService;

    public function __construct(SlotAvailabilityService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * Get available slots for a doctor on a given date
     * 
     * GET /api/slots?doctor_id=UUID&date=2026-01-15
     */
    public function index(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $slots = $this->slotService->getAvailableSlots(
            $request->doctor_id,
            $request->date,
            $request->slot_duration ?? null
        );

        return response()->json([
            'slots' => $slots,
            'doctor_id' => $request->doctor_id,
            'date' => $request->date,
        ]);
    }

    /**
     * Check availability of a specific slot
     * 
     * POST /api/slots/check
     */
    public function check(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'slot_start' => 'required|date',
            'slot_end' => 'required|date|after:slot_start',
        ]);

        $isAvailable = $this->slotService->isSlotAvailable(
            $request->doctor_id,
            \Carbon\Carbon::parse($request->slot_start),
            \Carbon\Carbon::parse($request->slot_end)
        );

        return response()->json([
            'available' => $isAvailable,
            'slot_start' => $request->slot_start,
            'slot_end' => $request->slot_end,
        ]);
    }
}
