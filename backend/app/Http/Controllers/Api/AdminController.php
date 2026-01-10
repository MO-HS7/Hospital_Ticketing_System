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
     */
    public function auditLog(Request $request)
    {
        $query = TicketEvent::select(['id', 'ticket_id', 'user_id', 'event_type', 'meta', 'created_at'])
            ->with([
                'user:id,name',
                'ticket:id,subject,type,department_id',
                'ticket.department:id,name_en,name_ar',
            ]);

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        return response()->json($query->latest()->paginate(20));
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
        $health = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'timestamp' => now()->toIso8601String(),
        ];

        $health['overall'] = collect($health)
            ->filter(fn($v) => is_array($v) && isset($v['status']))
            ->every(fn($v) => $v['status'] === 'healthy') ? 'healthy' : 'degraded';

        return response()->json($health);
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
