<?php

namespace App\Traits;

use App\Services\AuditContext;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Auditable Trait
 * 
 * Add to any model to enable automatic audit logging.
 * 
 * Configuration options (define as properties on your model):
 * 
 * - $auditInclude (array): Whitelist of fields to audit (if set, only these fields are tracked)
 * - $auditExclude (array): Blacklist of fields to exclude from auditing
 * - $auditMask (array): Fields whose values should be masked (shown as ***)
 * - $auditSkipNoisy (bool): Skip events where only noisy fields changed (default: true)
 * - $auditEvents (array): Events to track: ['created', 'updated', 'deleted'] (default: all)
 * 
 * Example:
 * 
 * class User extends Model {
 *     use Auditable;
 *     protected $auditInclude = ['name', 'email', 'role'];
 *     protected $auditMask = ['phone'];
 * }
 */
trait Auditable
{
    /**
     * Boot the trait.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            if ($model->shouldAuditEvent('created')) {
                $model->logAuditEvent('created');
            }
        });

        static::updated(function (Model $model) {
            if ($model->shouldAuditEvent('updated') && $model->hasMeaningfulChanges()) {
                $model->logAuditEvent('updated', $model->getOriginal());
            }
        });

        static::deleted(function (Model $model) {
            if ($model->shouldAuditEvent('deleted')) {
                $model->logAuditEvent('deleted');
            }
        });
    }

    /**
     * Check if event type should be audited.
     */
    protected function shouldAuditEvent(string $event): bool
    {
        $events = property_exists($this, 'auditEvents') 
            ? $this->auditEvents 
            : ['created', 'updated', 'deleted'];
        
        return in_array($event, $events, true);
    }

    /**
     * Check if model has meaningful (non-noisy) changes.
     */
    protected function hasMeaningfulChanges(): bool
    {
        if (!property_exists($this, 'auditSkipNoisy') || $this->auditSkipNoisy === false) {
            return true;
        }

        $changes = $this->getChanges();
        $noisyFields = config('audit.noisy_fields', []);
        
        $meaningfulChanges = array_diff_key($changes, array_flip($noisyFields));
        
        return !empty($meaningfulChanges);
    }

    /**
     * Log the audit event.
     */
    protected function logAuditEvent(string $eventType, ?array $original = null): void
    {
        try {
            /** @var AuditLogger $logger */
            $logger = App::make(AuditLogger::class);
            
            switch ($eventType) {
                case 'created':
                    $logger->logCreated($this, $this->getAuditMeta());
                    break;
                    
                case 'updated':
                    $logger->logUpdated($this, $original ?? [], $this->getAuditMeta());
                    break;
                    
                case 'deleted':
                    $logger->logDeleted($this, $this->getAuditMeta());
                    break;
            }
        } catch (\Throwable $e) {
            // Log error but don't break the main operation
            \Log::error('Audit logging failed', [
                'error' => $e->getMessage(),
                'model' => get_class($this),
                'id' => $this->getKey(),
                'event' => $eventType,
            ]);
        }
    }

    /**
     * Get additional meta for audit event.
     * Override in model to add custom metadata.
     */
    protected function getAuditMeta(): ?array
    {
        return null;
    }

    /**
     * Get audit include list.
     */
    public function getAuditInclude(): array
    {
        return property_exists($this, 'auditInclude') ? $this->auditInclude : [];
    }

    /**
     * Get audit exclude list.
     */
    public function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }

    /**
     * Get audit mask list.
     */
    public function getAuditMask(): array
    {
        return property_exists($this, 'auditMask') ? $this->auditMask : [];
    }
}
