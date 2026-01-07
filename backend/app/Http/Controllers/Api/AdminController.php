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

            // Department stats with single query
            $departmentsStats = DB::table('tickets')
                ->join('departments', 'tickets.department_id', '=', 'departments.id')
                ->selectRaw('departments.id, departments.name_en, departments.name_ar, COUNT(*) as count')
                ->groupBy('departments.id', 'departments.name_en', 'departments.name_ar')
                ->get()
                ->map(fn($row) => [
                    'id' => $row->id,
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
    }
}
