<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use App\Models\TicketEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get dashboard metrics with caching.
     * Cached for 1 minute to reduce load on frequent dashboard visits.
     */
    public function metrics()
    {
        $metrics = Cache::remember('admin:metrics', 60, function () {
            // Use raw queries for counts to minimize overhead
            $ticketCounts = DB::table('tickets')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status IN ('completed', 'closed_late') THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'overdue' OR (deadline < NOW() AND completed_at IS NULL AND deadline IS NOT NULL) THEN 1 ELSE 0 END) as overdue
                ")
                ->first();

            // Department stats with single query - includes slug for icon mapping
            $departmentsStats = DB::table('tickets')
                ->join('departments', 'tickets.department_id', '=', 'departments.id')
                ->selectRaw('departments.id, departments.slug, departments.name_en, departments.name_ar, COUNT(*) as count')
                ->groupBy('departments.id', 'departments.slug', 'departments.name_en', 'departments.name_ar')
                ->get()
                ->map(fn($row) => [
                    'id' => $row->id,
                    'slug' => $row->slug,
                    'name_en' => $row->name_en,
                    'name_ar' => $row->name_ar,
                    'count' => (int) $row->count,
                ])
                ->values();

            // Staff counts with single query using role
            $staffCounts = DB::table('users')
                ->join('model_has_roles', function ($join) {
                    $join->on('users.id', '=', 'model_has_roles.model_id')
                         ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
                })
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereIn('roles.name', ['doctor', 'maintenance', 'reception'])
                ->selectRaw("roles.name as role, COUNT(*) as count")
                ->groupBy('roles.name')
                ->pluck('count', 'role');

            return [
                'tickets' => [
                    'total' => (int) $ticketCounts->total,
                    'pending' => (int) $ticketCounts->pending,
                    'in_progress' => (int) $ticketCounts->in_progress,
                    'completed' => (int) $ticketCounts->completed,
                    'overdue' => (int) $ticketCounts->overdue,
                ],
                'departments_stats' => $departmentsStats,
                'staff' => [
                    'doctors' => (int) ($staffCounts['doctor'] ?? 0),
                    'maintenance' => (int) ($staffCounts['maintenance'] ?? 0),
                    'reception' => (int) ($staffCounts['reception'] ?? 0),
                ],
            ];
        });

        // Overdue tickets are fetched separately (not cached as long)
        $overdueTickets = Cache::remember('admin:overdue_tickets', 30, function () {
            return Ticket::with(['department:id,name_en,name_ar'])
                ->where(function ($q) {
                    $q->where('status', 'overdue')
                      ->orWhere(function ($q2) {
                          $q2->whereNotNull('deadline')
                             ->where('deadline', '<', now())
                             ->whereNull('completed_at');
                      });
                })
                ->orderByDesc('deadline')
                ->limit(10)
                ->get(['id', 'department_id', 'type', 'deadline', 'status'])
                ->map(fn(Ticket $t) => [
                    'id' => (string) $t->id,
                    'department' => [
                        'name_en' => $t->department?->name_en,
                        'name_ar' => $t->department?->name_ar,
                    ],
                    'type' => $t->type,
                    'deadline' => $t->deadline?->toDateTimeString(),
                    'status' => $t->status,
                ])
                ->values();
        });

        $metrics['overdue_tickets'] = $overdueTickets;

        return response()->json($metrics);
    }

    /**
     * Get audit log (ticket events) with optimized eager loading.
     * Supports enterprise-grade filtering for compliance audit trails.
     * 
     * Query params:
     *   - page, per_page (pagination)
     *   - from, to (date range, inclusive)
     *   - event_type (string)
     *   - q (search: ticket ID, subject, user name)
     *   - actor_id (int, who performed action)
     *   - role (string: doctor, admin, reception, etc.)
     *   - department_id (string/uuid)
     *   - has_diff (0/1: only events with old_value/new_value)
     */
    public function auditLog(Request $request)
    {
        $query = TicketEvent::select(['id', 'ticket_id', 'user_id', 'event_type', 'meta', 'created_at'])
            ->with([
                'user:id,name,email',
                'user.roles:id,name',
                'ticket:id,subject,type,department_id,patient_id,priority,status',
                'ticket.department:id,name_en,name_ar,slug',
                'ticket.patient:id,name',
            ]);

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by ticket ID
        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        // Filter by actor ID (who performed the action)
        if ($request->filled('actor_id')) {
            $query->where('user_id', $request->actor_id);
        }

        // Filter by date range (inclusive)
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Legacy support for date_from/date_to
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by role (via user's roles - Spatie)
        if ($request->filled('role')) {
            $query->whereHas('user.roles', fn($q) => $q->where('name', $request->role));
        }

        // Filter by department (via ticket join)
        if ($request->filled('department_id')) {
            $query->whereHas('ticket', fn($q) => $q->where('department_id', $request->department_id));
        }

        // Filter by has_diff (events with old_value/new_value in meta)
        if ($request->filled('has_diff') && $request->has_diff == '1') {
            $query->where(function ($q) {
                $q->whereRaw("JSON_EXTRACT(meta, '$.from') IS NOT NULL")
                  ->orWhereRaw("JSON_EXTRACT(meta, '$.to') IS NOT NULL")
                  ->orWhereRaw("JSON_EXTRACT(meta, '$.old_status') IS NOT NULL")
                  ->orWhereRaw("JSON_EXTRACT(meta, '$.new_status') IS NOT NULL");
            });
        }

        // Search filter (q): ticket ID, subject, user name
        if ($request->filled('q')) {
            $search = $request->q;
            
            // If numeric, treat as ticket ID
            if (is_numeric($search)) {
                $query->where('ticket_id', $search);
            } else {
                $query->where(function ($q) use ($search) {
                    // Search in ticket subject
                    $q->whereHas('ticket', fn($tq) => $tq->where('subject', 'like', "%{$search}%"))
                      // Search in user name
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            }
        }

        $perPage = min($request->input('per_page', 25), 50);

        $paginated = $query->latest()->paginate($perPage);

        // Transform response to include nested actor/ticket objects and summary_key
        $data = collect($paginated->items())->map(function ($event) {
            $meta = $event->meta ?? [];
            
            // Build summary params based on event type
            $summaryParams = $this->buildSummaryParams($event, $meta);
            
            return [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'created_at' => $event->created_at->toIso8601String(),
                
                // Nested actor object
                'actor' => $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                    'email' => $event->user->email,
                    'role' => $event->user->roles->first()?->name,
                ] : null,
                
                // Nested ticket object
                'ticket' => $event->ticket ? [
                    'id' => $event->ticket->id,
                    'number' => "#{$event->ticket->id}",
                    'subject' => $event->ticket->subject,
                    'type' => $event->ticket->type,
                    'priority' => $event->ticket->priority,
                    'status' => $event->ticket->status,
                    'department_id' => $event->ticket->department_id,
                    'department_name_en' => $event->ticket->department?->name_en,
                    'department_name_ar' => $event->ticket->department?->name_ar,
                    'department_slug' => $event->ticket->department?->slug,
                    'patient_id' => $event->ticket->patient_id,
                    'patient_name' => $event->ticket->patient?->name,
                ] : null,
                
                // i18n summary support
                'summary_key' => "audit.summary.{$event->event_type}",
                'summary_params' => $summaryParams,
                
                // Raw meta for diff/details view
                'old_value' => isset($meta['from']) ? ['status' => $meta['from']] : 
                              (isset($meta['old_status']) ? ['status' => $meta['old_status']] : null),
                'new_value' => isset($meta['to']) ? ['status' => $meta['to']] :
                              (isset($meta['new_status']) ? ['status' => $meta['new_status']] : null),
                'meta' => $meta,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Build summary params for i18n interpolation.
     */
    private function buildSummaryParams($event, array $meta): array
    {
        $params = [
            'actor' => $event->user?->name ?? 'System',
            'ticket' => $event->ticket_id,
        ];

        switch ($event->event_type) {
            case 'status_changed':
                $params['from'] = $meta['from'] ?? $meta['old_status'] ?? 'unknown';
                $params['to'] = $meta['to'] ?? $meta['new_status'] ?? 'unknown';
                break;
            case 'priority_changed':
                $params['from'] = $meta['from'] ?? 'unknown';
                $params['to'] = $meta['to'] ?? 'unknown';
                break;
            case 'assigned':
                $params['assignee'] = $meta['assigned_to'] ?? $meta['assignee_name'] ?? 'unknown';
                break;
            case 'note_added':
                $params['note'] = $meta['note'] ?? '';
                break;
        }

        return $params;
    }

    /**
     * Audit Log V2 - Uses new audit_events table with polymorphic auditing.
     * 
     * Query params:
     *   - All Phase 1 params (page, per_page, from, to, event_type, q, actor_id, role, department_id, has_diff)
     *   - auditable_type (string: ticket, user, payment, etc.)
     *   - auditable_id (int: specific resource ID)
     *   - include_facets (0/1: include aggregated counts)
     */
    public function auditLogV2(Request $request)
    {
        $query = \App\Models\AuditEvent::query()
            ->with(['actor:id,name,email', 'department:id,name_en,name_ar,slug']);

        // Filter by auditable type
        if ($request->filled('auditable_type')) {
            $query->ofType($request->auditable_type);
        }

        // Filter by specific auditable
        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->auditable_id);
        }

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->ofEventType($request->event_type);
        }

        // Filter by actor
        if ($request->filled('actor_id')) {
            $query->byActor($request->actor_id);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->byRole($request->role);
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->inDepartment($request->department_id);
        }

        // Filter by date range
        $query->dateRange($request->from, $request->to);

        // Filter by has_diff
        if ($request->filled('has_diff') && $request->has_diff == '1') {
            $query->withChanges();
        }

        // Search
        if ($request->filled('q')) {
            $query->search($request->q);
        }

        $perPage = min($request->input('per_page', 25), 50);
        $paginated = $query->latest()->paginate($perPage);

        // Transform response
        $data = collect($paginated->items())->map(function ($event) {
            return [
                'id' => $event->id,
                'auditable_type' => $event->auditable_type,
                'auditable_id' => $event->auditable_id,
                'event_type' => $event->event_type,
                'created_at' => $event->created_at->toIso8601String(),
                
                'actor' => $event->actor ? [
                    'id' => $event->actor->id,
                    'name' => $event->actor->name,
                    'email' => $event->actor->email,
                    'role' => $event->actor_role,
                ] : null,
                
                'department_id' => $event->department_id,
                'department' => $event->department ? [
                    'id' => $event->department->id,
                    'name_en' => $event->department->name_en,
                    'name_ar' => $event->department->name_ar,
                ] : null,
                
                // Request metadata
                'ip_address' => $event->ip_address,
                'user_agent' => $event->user_agent,
                'route' => $event->route,
                'method' => $event->method,
                'request_id' => $event->request_id,
                
                // i18n support
                'summary_key' => $event->summary_key ?? "audit.summary.{$event->auditable_type}.{$event->event_type}",
                'summary_params' => $event->summary_params ?? ['id' => $event->auditable_id],
                
                // Change data
                'old_value' => $event->old_value,
                'new_value' => $event->new_value,
                'meta' => $event->meta,
            ];
        });

        $response = [
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];

        // Add facets if requested
        if ($request->input('include_facets') == '1') {
            $response['facets'] = $this->buildAuditFacets($request);
        }

        return response()->json($response);
    }

    /**
     * Build facets for audit log (counts by event_type, role, department).
     */
    private function buildAuditFacets(Request $request): array
    {
        $baseQuery = \App\Models\AuditEvent::query();

        // Apply same filters (except pagination)
        if ($request->filled('auditable_type')) {
            $baseQuery->ofType($request->auditable_type);
        }
        if ($request->filled('from') || $request->filled('to')) {
            $baseQuery->dateRange($request->from, $request->to);
        }
        
        return [
            'by_event_type' => (clone $baseQuery)
                ->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->pluck('count', 'event_type')
                ->toArray(),
            'by_role' => (clone $baseQuery)
                ->whereNotNull('actor_role')
                ->selectRaw('actor_role, COUNT(*) as count')
                ->groupBy('actor_role')
                ->pluck('count', 'actor_role')
                ->toArray(),
            'by_department' => (clone $baseQuery)
                ->whereNotNull('department_id')
                ->selectRaw('department_id, COUNT(*) as count')
                ->groupBy('department_id')
                ->pluck('count', 'department_id')
                ->toArray(),
        ];
    }

    /**
     * Export audit log as CSV (streaming).
     */
    public function exportAuditLog(Request $request)
    {
        // Require date range for safety
        if (config('audit.export.require_date_range', true)) {
            if (!$request->filled('from') || !$request->filled('to')) {
                return response()->json([
                    'error' => 'Date range (from/to) is required for export'
                ], 422);
            }
            
            // Check max date range
            $maxDays = config('audit.export.max_date_range_days', 90);
            $from = \Carbon\Carbon::parse($request->from);
            $to = \Carbon\Carbon::parse($request->to);
            if ($from->diffInDays($to) > $maxDays) {
                return response()->json([
                    'error' => "Date range cannot exceed {$maxDays} days"
                ], 422);
            }
        }

        $filename = 'audit_log_' . now()->format('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($request) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'ID', 'Type', 'Resource ID', 'Event', 'Actor', 'Actor Role',
                'Department', 'IP Address', 'Route', 'Method', 'Request ID',
                'Created At', 'Summary'
            ]);

            // Stream results in chunks
            $query = \App\Models\AuditEvent::query()
                ->with(['actor:id,name', 'department:id,name_en']);

            if ($request->filled('auditable_type')) {
                $query->ofType($request->auditable_type);
            }
            if ($request->filled('event_type')) {
                $query->ofEventType($request->event_type);
            }
            if ($request->filled('actor_id')) {
                $query->byActor($request->actor_id);
            }
            if ($request->filled('role')) {
                $query->byRole($request->role);
            }
            if ($request->filled('department_id')) {
                $query->inDepartment($request->department_id);
            }
            $query->dateRange($request->from, $request->to);

            $maxRows = config('audit.export.max_rows', 10000);
            $count = 0;

            $query->latest()->chunk(500, function ($events) use ($handle, &$count, $maxRows) {
                foreach ($events as $event) {
                    if ($count >= $maxRows) {
                        return false; // Stop chunking
                    }

                    fputcsv($handle, [
                        $event->id,
                        $event->auditable_type,
                        $event->auditable_id,
                        $event->event_type,
                        $event->actor?->name ?? 'System',
                        $event->actor_role ?? '',
                        $event->department?->name_en ?? '',
                        $event->ip_address ?? '',
                        $event->route ?? '',
                        $event->method ?? '',
                        $event->request_id ?? '',
                        $event->created_at->toIso8601String(),
                        $event->summary_key ?? '',
                    ]);

                    $count++;
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Clear admin metrics cache (called after ticket updates)
     */
    public static function clearMetricsCache(): void
    {
        Cache::forget('admin:metrics');
        Cache::forget('admin:overdue_tickets');
        Cache::forget('admin:insights');
    }

    /**
     * Get smart insight cards data for command center.
     * Returns action-oriented insights: SLA risks, pending actions, peak departments.
     */
    public function insights()
    {
        $insights = Cache::remember('admin:insights', 30, function () {
            $now = now();
            $thirtyMinutesFromNow = $now->copy()->addMinutes(30);

            // Tickets at SLA risk (deadline within 30 minutes)
            $atRiskTickets = DB::table('tickets')
                ->whereNotNull('deadline')
                ->whereNull('completed_at')
                ->where('deadline', '>', $now)
                ->where('deadline', '<=', $thirtyMinutesFromNow)
                ->whereNotIn('status', ['completed', 'closed_late'])
                ->count();

            // Tickets awaiting payment (reception action needed)
            $awaitingPayment = DB::table('tickets')
                ->where('status', 'awaiting_payment')
                ->count();

            // Emergency tickets still pending
            $emergencyPending = DB::table('tickets')
                ->where('priority', 'urgent')
                ->whereNotIn('status', ['completed', 'closed_late'])
                ->count();

            // Peak department today (most tickets created)
            $peakDepartment = DB::table('tickets')
                ->join('departments', 'tickets.department_id', '=', 'departments.id')
                ->whereDate('tickets.created_at', $now->toDateString())
                ->selectRaw('departments.id, departments.name_en, departments.name_ar, COUNT(*) as count')
                ->groupBy('departments.id', 'departments.name_en', 'departments.name_ar')
                ->orderByDesc('count')
                ->first();

            // Ticket source breakdown (Manual vs Chatbot)
            $sourceBreakdown = DB::table('tickets')
                ->selectRaw("
                    SUM(CASE WHEN source = 'chatbot' THEN 1 ELSE 0 END) as chatbot,
                    SUM(CASE WHEN source = 'manual' OR source IS NULL THEN 1 ELSE 0 END) as manual
                ")
                ->first();

            // Today's ticket counts for trends
            $todayStats = DB::table('tickets')
                ->whereDate('created_at', $now->toDateString())
                ->selectRaw("
                    COUNT(*) as total_today,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today
                ")
                ->first();

            return [
                'at_risk' => [
                    'count' => $atRiskTickets,
                    'severity' => $atRiskTickets > 5 ? 'critical' : ($atRiskTickets > 0 ? 'warning' : 'success'),
                ],
                'awaiting_payment' => [
                    'count' => $awaitingPayment,
                    'severity' => $awaitingPayment > 10 ? 'warning' : ($awaitingPayment > 0 ? 'info' : 'success'),
                ],
                'emergency_pending' => [
                    'count' => $emergencyPending,
                    'severity' => $emergencyPending > 0 ? 'critical' : 'success',
                ],
                'peak_department' => $peakDepartment ? [
                    'id' => $peakDepartment->id,
                    'name_en' => $peakDepartment->name_en,
                    'name_ar' => $peakDepartment->name_ar,
                    'count' => (int) $peakDepartment->count,
                ] : null,
                'source_breakdown' => [
                    'chatbot' => (int) ($sourceBreakdown->chatbot ?? 0),
                    'manual' => (int) ($sourceBreakdown->manual ?? 0),
                ],
                'today' => [
                    'total' => (int) ($todayStats->total_today ?? 0),
                    'completed' => (int) ($todayStats->completed_today ?? 0),
                ],
            ];
        });

        return response()->json($insights);
    }

    /**
     * Get live activity feed with filters.
     */
    public function activityFeed(Request $request)
    {
        $query = TicketEvent::select(['id', 'ticket_id', 'user_id', 'event_type', 'meta', 'created_at'])
            ->with([
                'user:id,name',
                'ticket:id,subject,type,department_id,source',
                'ticket.department:id,name_en,name_ar',
            ]);

        // Filter by department
        if ($request->filled('department_id')) {
            $query->whereHas('ticket', fn($q) => $q->where('department_id', $request->department_id));
        }

        // Filter by source (manual/chatbot)
        if ($request->filled('source')) {
            $query->whereHas('ticket', fn($q) => $q->where('source', $request->source));
        }

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by role (via user)
        if ($request->filled('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->whereHas('roles', fn($r) => $r->where('name', $request->role));
            });
        }

        $perPage = min($request->input('per_page', 20), 50);

        return response()->json($query->latest()->paginate($perPage));
    }

    /**
     * Get system health status.
     */
    public function systemHealth()
    {
        $database = $this->checkDatabase();
        $cache = $this->checkCache();
        $queue = $this->checkQueue();
        $storage = $this->checkStorage();
        
        $services = [
            'database' => $database,
            'cache' => $cache,
            'queue' => $queue,
            'storage' => $storage,
        ];
        
        // Determine overall status and build reason summary
        $reasons = [];
        $hasCritical = false;
        $hasDegraded = false;
        
        foreach ($services as $name => $service) {
            $status = $service['status'] ?? 'unknown';
            if ($status === 'unhealthy' || $status === 'down') {
                $hasCritical = true;
                $reasons[] = ucfirst($name) . ' is ' . $status;
            } elseif ($status === 'degraded' || $status === 'warning') {
                $hasDegraded = true;
                $reasons[] = ucfirst($name) . ' is ' . $status;
            } elseif ($status === 'unknown') {
                $hasDegraded = true;
                $reasons[] = ucfirst($name) . ' status unknown';
            }
        }
        
        $overallStatus = 'healthy';
        if ($hasCritical) {
            $overallStatus = 'unhealthy';
        } elseif ($hasDegraded) {
            $overallStatus = 'degraded';
        }
        
        $reasonSummary = empty($reasons) ? 'All systems operational' : implode(', ', $reasons);
        
        return response()->json([
            'overall_status' => $overallStatus,
            'overall' => $overallStatus, // Legacy support
            'reason_summary' => $reasonSummary,
            'timestamp' => now()->toIso8601String(),
            'last_checked_at' => now()->toIso8601String(),
            'database' => $database,
            'cache' => $cache,
            'queue' => $queue,
            'storage' => $storage,
            'app' => [
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
            ],
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            return ['status' => 'healthy', 'latency_ms' => $latency];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', true, 10);
            $result = Cache::get('health_check');
            $latency = round((microtime(true) - $start) * 1000, 2);
            return ['status' => $result ? 'healthy' : 'unhealthy', 'latency_ms' => $latency, 'driver' => config('cache.default')];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $failed = DB::table('failed_jobs')->count();
            return ['status' => $failed > 10 ? 'degraded' : 'healthy', 'failed_jobs' => $failed];
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $free = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            $usedPercent = round((1 - $free / $total) * 100, 1);
            return [
                'status' => $usedPercent > 90 ? 'warning' : 'healthy',
                'used_percent' => $usedPercent,
                'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            ];
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get operational bottlenecks data.
     */
    public function bottlenecks()
    {
        $bottlenecks = Cache::remember('admin:bottlenecks', 60, function () {
            $now = now();

            // Most delayed department (highest avg overdue time)
            $delayedDepartment = DB::table('tickets')
                ->join('departments', 'tickets.department_id', '=', 'departments.id')
                ->whereNotNull('deadline')
                ->where('deadline', '<', $now)
                ->whereNull('completed_at')
                ->selectRaw('departments.id, departments.name_en, departments.name_ar, COUNT(*) as overdue_count')
                ->groupBy('departments.id', 'departments.name_en', 'departments.name_ar')
                ->orderByDesc('overdue_count')
                ->first();

            // Oldest unhandled ticket
            $oldestTicket = Ticket::with(['department:id,name_en,name_ar', 'patient:id,name'])
                ->whereIn('status', ['pending', 'assigned'])
                ->orderBy('created_at')
                ->first(['id', 'subject', 'department_id', 'patient_id', 'created_at', 'status']);

            // Most repeated maintenance issue (by subject pattern)
            $repeatedIssue = DB::table('tickets')
                ->where('type', 'maintenance')
                ->whereDate('created_at', '>=', $now->copy()->subDays(7))
                ->selectRaw('subject, COUNT(*) as count')
                ->groupBy('subject')
                ->orderByDesc('count')
                ->first();

            // Departments under pressure (> 10 open tickets)
            $pressuredDepartments = DB::table('tickets')
                ->join('departments', 'tickets.department_id', '=', 'departments.id')
                ->whereNotIn('tickets.status', ['completed', 'closed_late'])
                ->selectRaw('departments.id, departments.name_en, departments.name_ar, COUNT(*) as open_count')
                ->groupBy('departments.id', 'departments.name_en', 'departments.name_ar')
                ->having('open_count', '>', 10)
                ->orderByDesc('open_count')
                ->get();

            return [
                'delayed_department' => $delayedDepartment ? [
                    'id' => $delayedDepartment->id,
                    'name_en' => $delayedDepartment->name_en,
                    'name_ar' => $delayedDepartment->name_ar,
                    'overdue_count' => (int) $delayedDepartment->overdue_count,
                ] : null,
                'oldest_ticket' => $oldestTicket ? [
                    'id' => $oldestTicket->id,
                    'subject' => $oldestTicket->subject,
                    'department' => $oldestTicket->department,
                    'patient_name' => $oldestTicket->patient?->name,
                    'created_at' => $oldestTicket->created_at->toIso8601String(),
                    'age_hours' => $oldestTicket->created_at->diffInHours($now),
                ] : null,
                'repeated_issue' => $repeatedIssue ? [
                    'subject' => $repeatedIssue->subject,
                    'count' => (int) $repeatedIssue->count,
                ] : null,
                'pressured_departments' => $pressuredDepartments->map(fn($d) => [
                    'id' => $d->id,
                    'name_en' => $d->name_en,
                    'name_ar' => $d->name_ar,
                    'open_count' => (int) $d->open_count,
                ])->values(),
            ];
        });

        return response()->json($bottlenecks);
    }
}
