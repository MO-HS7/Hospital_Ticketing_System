<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'creator_id',
        'department_id',
        'assigned_to',
        'type',
        'status',
        'priority',
        'source', // 'manual' or 'chatbot'
        'subject',
        'description',
        'scheduled_at',
        'deadline',
        'accepted_at',
        'started_at',
        'completed_at',
        'time_spent_minutes',
        // Patient Info (Step 3 enhancement)
        'patient_age',
        'patient_gender',
        'contact_method',
        'contact_phone',
        'is_emergency',
        'medical_conditions',
        'additional_notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_minutes' => 'integer',
        'is_emergency' => 'boolean',
        'patient_age' => 'integer',
    ];


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

    public function isOverdue(): bool
    {
        return $this->deadline && now()->isAfter($this->deadline) && !$this->completed_at;
    }
}
