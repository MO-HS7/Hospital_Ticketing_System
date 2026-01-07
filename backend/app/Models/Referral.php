<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'from_ticket_id',
        'to_ticket_id',
        'to_department_id',
        'referred_by',
        'reason',
        'status',
        'priority',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the encounter this referral belongs to.
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class, 'encounter_id', 'id');
    }

    /**
     * Get the original ticket (from).
     */
    public function fromTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'from_ticket_id');
    }

    /**
     * Get the new referred ticket (to).
     */
    public function toTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'to_ticket_id');
    }

    /**
     * Get the target department.
     */
    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id', 'id');
    }

    /**
     * Get the user who created this referral.
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
}
