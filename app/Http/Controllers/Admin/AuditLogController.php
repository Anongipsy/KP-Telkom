<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AuditLogController — PRD FR-16, Section 4.2
 *
 * Admin view for inspecting WHO, WHAT, WHEN, TARGET audit trail.
 */
class AuditLogController extends Controller
{
    /**
     * Display a paginated list of audit logs with filters.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest();

        // Filter by action
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        // Filter by target type
        if ($targetType = $request->input('target_type')) {
            $query->where('target_type', $targetType);
        }

        // Search in target_reference or action
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('target_reference', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        // Get distinct actions for dropdown filter
        $availableActions = AuditLog::distinct()->pluck('action')->sort()->values();

        return view('admin.audit-logs', [
            'logs' => $logs,
            'availableActions' => $availableActions,
            'filters' => [
                'action' => $action,
                'target_type' => $targetType,
                'q' => $search,
            ],
        ]);
    }
}
