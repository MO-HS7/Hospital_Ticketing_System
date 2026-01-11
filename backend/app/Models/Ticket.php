<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    // Status constants for slot-based workflow
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_QUEUE = 'in_queue';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CLOSED_LATE = 'closed_late';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'creator_id',
        'department_id',
        'assigned_to',
        'type',
        'status',
        'priority',
        'source',
        'subject',
        'description',
        // Slot-based scheduling
        'slot_start',
        'slot_end',
        'slot_duration',
        'scheduled_at',
        // SLA configuration
        'sla_response_minutes',
        'sla_resolution_minutes',
        // Timestamps
        'deadline',
        'accepted_at',
        'started_at',
        'response_started_at',
        'resolution_started_at',
        'completed_at',
        'time_spent_minutes',
        // Patient Info
        'patient_age',
        'patient_gender',
        'contact_method',
        'contact_phone',
        'is_emergency',
        'medical_conditions',
        'additional_notes',
    ];

    protected $casts = [
        'slot_start' => 'datetime',
        'slot_end' => 'datetime',
        'slot_duration' => 'integer',
        'scheduled_at' => 'datetime',
        'deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'response_started_at' => 'datetime',
        'resolution_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_minutes' => 'integer',
        'sla_response_minutes' => 'integer',
        'sla_resolution_minutes' => 'integer',
        'is_emergency' => 'boolean',
        'patient_age' => 'integer',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }

    public function notes()
    {
        return $this->hasMany(TicketNote::class);
    }

    public function events()
    {
        return $this->hasMany(TicketEvent::class);
    }

    // Slot-based status checks
    public function isSlotActive(): bool
    {
        if (!$this->slot_start || !$this->slot_end) {
            return false;
        }
        $now = now();
        return $now->gte($this->slot_start) && $now->lt($this->slot_end);
    }

    public function isSlotPast(): bool
    {
        return $this->slot_end && now()->gte($this->slot_end);
    }

    public function isSlotFuture(): bool
    {
        return $this->slot_start && now()->lt($this->slot_start);
    }

    // SLA calculations (slot-based)
    public function isResponseSlaBreached(): bool
    {
        if ($this->status !== self::STATUS_IN_QUEUE) {
            return false;
        }
        if (!$this->slot_start) {
            return false;
        }
        $deadline = $this->slot_start->addMinutes($this->sla_response_minutes ?? 30);
        return now()->isAfter($deadline);
    }

    public function isResolutionSlaBreached(): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }
        if (!$this->resolution_started_at) {
            return false;
        }
        $deadline = $this->resolution_started_at->addMinutes($this->sla_resolution_minutes ?? 1440);
        return now()->isAfter($deadline);
    }

    public function getResponseSlaRemainingMinutes(): ?int
    {
        if (!$this->slot_start || $this->response_started_at) {
            return null;
        }
        $deadline = $this->slot_start->addMinutes($this->sla_response_minutes ?? 30);
        return max(0, now()->diffInMinutes($deadline, false));
    }

    public function getResolutionSlaRemainingMinutes(): ?int
    {
        if (!$this->resolution_started_at || $this->completed_at) {
            return null;
        }
        $deadline = $this->resolution_started_at->addMinutes($this->sla_resolution_minutes ?? 1440);
        return max(0, now()->diffInMinutes($deadline, false));
    }

    public function isOverdue(): bool
    {
        return $this->deadline && now()->isAfter($this->deadline) && !$this->completed_at;
    }
}
