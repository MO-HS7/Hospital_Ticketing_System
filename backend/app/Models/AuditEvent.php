<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * AuditEvent Model
 * 
 * Represents a compliance-grade audit log entry with polymorphic auditing,
 * request metadata, and support for system-wide event tracking.
 * 
 * @property int $id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property int|null $actor_id
 * @property string|null $actor_role
 * @property string $event_type
 * @property int|null $department_id
 * @property string|null $summary_key
 * @property array|null $summary_params
 * @property array|null $old_value
 * @property array|null $new_value
 * @property array|null $meta
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $route
 * @property string|null $method
 * @property string|null $request_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AuditEvent extends Model
{
    protected $table = 'audit_events';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'actor_id',
        'actor_role',
        'event_type',
        'department_id',
        'summary_key',
        'summary_params',
        'old_value',
        'new_value',
        'meta',
        'ip_address',
        'user_agent',
        'route',
        'method',
        'request_id',
    ];

    protected $casts = [
        'summary_params' => 'array',
        'old_value' => 'array',
        'new_value' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Polymorphic relationship to auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'auditable_type', 'auditable_id')
            ->withDefault();
    }

    /**
     * Actor (user who performed the action).
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Department relationship.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filter by auditable type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Filter by specific auditable.
     */
    public function scopeForAuditable(Builder $query, string $type, int $id): Builder
    {
        return $query->where('auditable_type', $type)
            ->where('auditable_id', $id);
    }

    /**
     * Filter by event type.
     */
    public function scopeOfEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Filter by actor.
     */
    public function scopeByActor(Builder $query, int $actorId): Builder
    {
        return $query->where('actor_id', $actorId);
    }

    /**
     * Filter by actor role.
     */
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('actor_role', $role);
    }

    /**
     * Filter by department.
     */
    public function scopeInDepartment(Builder $query, string $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Filter by date range.
     */
    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
        return $query;
    }

    /**
     * Filter events with actual changes.
     */
    public function scopeWithChanges(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('old_value')
                ->orWhereNotNull('new_value')
                ->orWhereJsonLength('meta', '>', 0);
        });
    }

    /**
     * Search across multiple fields.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('event_type', 'like', $term)
                ->orWhere('ip_address', 'like', $term)
                ->orWhere('request_id', 'like', $term)
                ->orWhereHas('actor', fn($a) => $a->where('name', 'like', $term));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve auditable type to model class.
     */
    public function getAuditableModelClass(): ?string
    {
        $typeMap = config('audit.type_map', []);
        return $typeMap[$this->auditable_type] ?? null;
    }

    /**
     * Check if event has any changes recorded.
     */
    public function hasChanges(): bool
    {
        return !empty($this->old_value) || !empty($this->new_value) || !empty($this->meta);
    }

    /**
     * Get the auditable model instance.
     */
    public function getAuditableModel(): ?Model
    {
        $class = $this->getAuditableModelClass();
        if (!$class || !class_exists($class)) {
            return null;
        }
        return $class::find($this->auditable_id);
    }

    /**
     * Create from legacy ticket event (for migration/compatibility).
     */
    public static function fromTicketEvent(TicketEvent $ticketEvent): self
    {
        return new self([
            'auditable_type' => 'ticket',
            'auditable_id' => $ticketEvent->ticket_id,
            'actor_id' => $ticketEvent->user_id,
            'actor_role' => $ticketEvent->user?->roles->first()?->name,
            'event_type' => $ticketEvent->event_type,
            'department_id' => $ticketEvent->ticket?->department_id,
            'meta' => $ticketEvent->meta,
            'created_at' => $ticketEvent->created_at,
            'updated_at' => $ticketEvent->updated_at,
        ]);
    }
}
