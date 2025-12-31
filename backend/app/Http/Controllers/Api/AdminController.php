<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get dashboard metrics.
     */
    public function metrics()
    {
        return response()->json([
            'total_tickets' => Ticket::count(),
            'pending_tickets' => Ticket::where('status', 'pending')->count(),
            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
            'completed_tickets' => Ticket::where('status', 'completed')->count(),
            'overdue_tickets' => Ticket::where('status', 'overdue')
                ->orWhere(function ($q) {
                    $q->whereNotNull('deadline')
                      ->where('deadline', '<', now())
                      ->whereNull('completed_at');
                })->count(),
            'closed_late_tickets' => Ticket::where('status', 'closed_late')->count(),
            'total_users' => User::count(),
            'total_departments' => Department::where('is_active', true)->count(),
            'tickets_by_department' => Ticket::selectRaw('department_id, count(*) as count')
                ->groupBy('department_id')
                ->with('department:id,name')
                ->get(),
            'tickets_by_status' => Ticket::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get(),
        ]);
    }

    /**
     * Get audit log (placeholder).
     */
    public function auditLog(Request $request)
    {
        // TODO: Implement audit logging
        return response()->json([
            'data' => [],
            'message' => 'Audit log not yet implemented',
        ]);
    }
}
