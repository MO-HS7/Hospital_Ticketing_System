<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class SlotAvailabilityService
{
    // Default slot configuration
    const DEFAULT_SLOT_DURATION = 30; // minutes
    const DEFAULT_SLOT_CAPACITY = 3;  // tickets per slot
    const SHIFT_START_HOUR = 8;       // 8 AM
    const SHIFT_END_HOUR = 17;        // 5 PM

    /**
     * Get available slots for a doctor on a given date
     */
    public function getAvailableSlots(string $doctorId, string $date, int $slotDuration = null): array
    {
        $slotDuration = $slotDuration ?? self::DEFAULT_SLOT_DURATION;
        $targetDate = Carbon::parse($date);
        
        // Don't allow booking in the past
        if ($targetDate->endOfDay()->lt(now())) {
            return [];
        }

        // Generate all possible slots for the day
        $slots = $this->generateSlotsForDay($targetDate, $slotDuration);
        
        // Get booked ticket counts per slot for this doctor
        $bookedCounts = $this->getBookedCountsForDoctor($doctorId, $targetDate);
        
        // Calculate availability for each slot
        return $slots->map(function ($slot) use ($bookedCounts) {
            $slotKey = $slot['slot_start']->format('H:i');
            $booked = $bookedCounts[$slotKey] ?? 0;
            $capacity = self::DEFAULT_SLOT_CAPACITY;
            $available = $capacity - $booked;
            
            return [
                'slot_start' => $slot['slot_start']->toIso8601String(),
                'slot_end' => $slot['slot_end']->toIso8601String(),
                'capacity' => $capacity,
                'booked' => $booked,
                'available' => $available,
                'status' => $this->getSlotStatus($available, $capacity),
            ];
        })->values()->toArray();
    }

    /**
     * Generate all time slots for a day based on shift hours
     */
    protected function generateSlotsForDay(Carbon $date, int $slotDuration): Collection
    {
        $slots = collect();
        
        $shiftStart = $date->copy()->setHour(self::SHIFT_START_HOUR)->setMinute(0)->setSecond(0);
        $shiftEnd = $date->copy()->setHour(self::SHIFT_END_HOUR)->setMinute(0)->setSecond(0);
        
        // If date is today, start from next available slot
        if ($date->isToday() && now()->gt($shiftStart)) {
            $minutesSinceShiftStart = now()->diffInMinutes($shiftStart);
            $slotsToSkip = ceil($minutesSinceShiftStart / $slotDuration);
            $shiftStart = $shiftStart->addMinutes($slotsToSkip * $slotDuration);
        }
        
        $current = $shiftStart->copy();
        
        while ($current->lt($shiftEnd)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            
            if ($slotEnd->gt($shiftEnd)) {
                break;
            }
            
            $slots->push([
                'slot_start' => $current->copy(),
                'slot_end' => $slotEnd->copy(),
            ]);
            
            $current = $slotEnd;
        }
        
        return $slots;
    }

    /**
     * Get booked ticket counts per slot for a doctor on a date
     */
    protected function getBookedCountsForDoctor(string $doctorId, Carbon $date): array
    {
        $tickets = Ticket::where('assigned_to', $doctorId)
            ->whereDate('slot_start', $date)
            ->whereNotIn('status', [Ticket::STATUS_CANCELLED, Ticket::STATUS_NO_SHOW])
            ->get();
        
        $counts = [];
        foreach ($tickets as $ticket) {
            if ($ticket->slot_start) {
                $key = $ticket->slot_start->format('H:i');
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }
        
        return $counts;
    }

    /**
     * Determine slot availability status
     */
    protected function getSlotStatus(int $available, int $capacity): string
    {
        if ($available <= 0) {
            return 'full';
        }
        if ($available === 1) {
            return 'limited';
        }
        return 'available';
    }

    /**
     * Check if a specific slot is available for booking
     */
    public function isSlotAvailable(string $doctorId, Carbon $slotStart, Carbon $slotEnd): bool
    {
        $booked = Ticket::where('assigned_to', $doctorId)
            ->where('slot_start', $slotStart)
            ->whereNotIn('status', [Ticket::STATUS_CANCELLED, Ticket::STATUS_NO_SHOW])
            ->count();
        
        return $booked < self::DEFAULT_SLOT_CAPACITY;
    }

    /**
     * Book a slot (create ticket with slot fields)
     */
    public function bookSlot(array $ticketData, Carbon $slotStart, int $slotDuration = null): ?Ticket
    {
        $slotDuration = $slotDuration ?? self::DEFAULT_SLOT_DURATION;
        $slotEnd = $slotStart->copy()->addMinutes($slotDuration);
        
        // Verify slot is still available
        if (!$this->isSlotAvailable($ticketData['assigned_to'], $slotStart, $slotEnd)) {
            return null;
        }
        
        // Create ticket with slot data
        $ticket = Ticket::create(array_merge($ticketData, [
            'slot_start' => $slotStart,
            'slot_end' => $slotEnd,
            'slot_duration' => $slotDuration,
            'status' => Ticket::STATUS_SCHEDULED,
            'scheduled_at' => now(),
        ]));
        
        return $ticket;
    }
}
