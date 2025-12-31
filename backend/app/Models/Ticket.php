<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'department_id',
        'assigned_to',
        'type',
        'status',
        'priority',
        'subject',
        'description',
        'scheduled_at',
        'deadline',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->deadline && now()->isAfter($this->deadline) && !$this->completed_at;
    }
}
