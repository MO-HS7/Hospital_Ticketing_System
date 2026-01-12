<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Centralized audit event logger.
 * 
 * Uses AuditContext for request metadata and respects field exclusion/masking rules.
 */
class AuditLogger
{
    protected AuditContext $context;

    public function __construct(AuditContext $context)
    {
        $this->context = $context;
    }

    /**
     * Log an audit event.
     */
    public function log(
        Model $auditable,
        string $eventType,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?array $meta = null,
        ?string $summaryKey = null,
        ?array $summaryParams = null,
        ?string $departmentId = null
    ): AuditEvent {
        // Ensure context is initialized
        if (!$this->context->isInitialized()) {
            $this->context->initialize();
        }

        // Get short type name from model
        $auditableType = $this->getShortTypeName($auditable);

        // Build event data
        $eventData = [
            'auditable_type' => $auditableType,
            'auditable_id' => $auditable->getKey(),
            'event_type' => $eventType,
            'department_id' => $departmentId ?? $this->extractDepartmentId($auditable),
            'summary_key' => $summaryKey ?? $this->generateSummaryKey($auditableType, $eventType),
            'summary_params' => $summaryParams ?? $this->generateSummaryParams($auditable, $eventType),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'meta' => $meta,
            ...$this->context->toArray(),
        ];

        return AuditEvent::create($eventData);
    }

    /**
     * Log a model creation event.
     */
    public function logCreated(Model $model, ?array $meta = null): AuditEvent
    {
        $filteredAttributes = $this->filterAttributes($model, $model->getAttributes());
        
        return $this->log(
            $model,
            'created',
            null,
            $filteredAttributes,
            $meta
        );
    }

    /**
     * Log a model update event.
     */
    public function logUpdated(Model $model, array $originalAttributes, ?array $meta = null): AuditEvent
    {
        $changes = $model->getChanges();
        
        // Filter to only changed, non-noisy fields
        $noisyFields = config('audit.noisy_fields', []);
        $meaningfulChanges = array_diff_key($changes, array_flip($noisyFields));
        
        if (empty($meaningfulChanges)) {
            // Skip noisy-only updates
            return new AuditEvent(); // Return empty model (not persisted)
        }
        
        $oldFiltered = $this->filterAttributes($model, array_intersect_key($originalAttributes, $changes));
        $newFiltered = $this->filterAttributes($model, $changes);
        
        return $this->log(
            $model,
            'updated',
            $oldFiltered,
            $newFiltered,
            $meta
        );
    }

    /**
     * Log a model deletion event.
     */
    public function logDeleted(Model $model, ?array $meta = null): AuditEvent
    {
        $filteredAttributes = $this->filterAttributes($model, $model->getAttributes());
        
        return $this->log(
            $model,
            'deleted',
            $filteredAttributes,
            null,
            $meta
        );
    }

    /**
     * Get short type name for model.
     */
    protected function getShortTypeName(Model $model): string
    {
        $typeMap = config('audit.type_map', []);
        $className = get_class($model);
        
        $shortName = array_search($className, $typeMap, true);
        
        return $shortName ?: strtolower(class_basename($model));
    }

    /**
     * Extract department_id from model if applicable.
     */
    protected function extractDepartmentId(Model $model): ?string
    {
        // Direct department_id
        if ($model->getAttribute('department_id')) {
            return (string) $model->department_id;
        }
        
        // Ticket model
        if (method_exists($model, 'department') && $model->department) {
            return (string) $model->department->id;
        }
        
        return null;
    }

    /**
     * Filter attributes based on model's audit rules.
     */
    protected function filterAttributes(Model $model, array $attributes): array
    {
        // Get exclude/mask rules from model or defaults
        $exclude = array_merge(
            config('audit.default_exclude', []),
            property_exists($model, 'auditExclude') ? $model->auditExclude : []
        );
        
        $include = property_exists($model, 'auditInclude') ? $model->auditInclude : [];
        
        $mask = array_merge(
            config('audit.default_mask', []),
            property_exists($model, 'auditMask') ? $model->auditMask : []
        );
        
        // Apply include filter (whitelist mode)
        if (!empty($include)) {
            $attributes = array_intersect_key($attributes, array_flip($include));
        }
        
        // Apply exclude filter
        $attributes = array_diff_key($attributes, array_flip($exclude));
        
        // Apply masking
        foreach ($mask as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = '***';
            }
        }
        
        return $attributes;
    }

    /**
     * Generate default summary key.
     */
    protected function generateSummaryKey(string $type, string $eventType): string
    {
        return "audit.summary.{$type}.{$eventType}";
    }

    /**
     * Generate default summary params.
     */
    protected function generateSummaryParams(Model $model, string $eventType): array
    {
        $params = [
            'id' => $model->getKey(),
        ];
        
        // Add common identifiers
        if ($model->getAttribute('name')) {
            $params['name'] = $model->name;
        }
        if ($model->getAttribute('subject')) {
            $params['subject'] = $model->subject;
        }
        if ($model->getAttribute('number')) {
            $params['number'] = $model->number;
        }
        
        // Add actor info from context
        if ($this->context->actorId) {
            $params['actor_id'] = $this->context->actorId;
        }
        
        return $params;
    }
}
