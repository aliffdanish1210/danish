<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogger;
use Exception;

class AuditLogController extends Controller
{
    /**
     * Display audit logs page
     */
    public function index(Request $request)
    {
        try {
            // Log admin viewing audit logs
            AuditLogger::logAdminAction('view_audit_logs', 'Admin viewed audit logs page');

            // Get filter parameters
            $eventType = $request->input('event_type');
            $severity = $request->input('severity');
            $status = $request->input('status');
            $search = $request->input('search');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $perPage = $request->input('per_page', 20);

            // Query audit logs
            $logs = AuditLog::query()
                ->when($eventType, fn($q) => $q->where('event_type', $eventType))
                ->when($severity, fn($q) => $q->where('severity', $severity))
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($search, function($q, $search) {
                    $q->where(function($query) use ($search) {
                        $query->where('user_name', 'LIKE', "%{$search}%")
                              ->orWhere('action', 'LIKE', "%{$search}%")
                              ->orWhere('description', 'LIKE', "%{$search}%")
                              ->orWhere('ip_address', 'LIKE', "%{$search}%");
                    });
                })
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Get statistics
            $stats = [
                'total' => AuditLog::count(),
                'today' => AuditLog::whereDate('created_at', today())->count(),
                'failed_logins' => AuditLog::where('action', 'login_failed')
                    ->whereDate('created_at', today())->count(),
                'suspicious' => AuditLog::where('status', 'suspicious')
                    ->whereDate('created_at', '>=', now()->subDays(7))->count(),
                'critical' => AuditLog::where('severity', 'critical')
                    ->whereDate('created_at', '>=', now()->subDays(7))->count(),
            ];

            // Get event types for filter
            $eventTypes = AuditLog::select('event_type')
                ->distinct()
                ->orderBy('event_type')
                ->pluck('event_type');

            return view('admin.audit-logs', compact('logs', 'stats', 'eventTypes'));

        } catch (Exception $e) {
            Log::error('Error loading audit logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('admin.audit-logs')
                ->with('error', 'Failed to load audit logs')
                ->with('logs', collect())
                ->with('stats', []);
        }
    }
public static function logLogin($userId, $success = true, $reason = null)
{
    self::log([
        'user_id' => null, // Will be filled from auth()->user() in log() method
        'user_name' => $userId, // This is actually user_id string
        'event_type' => 'authentication',
        'action' => $success ? 'login_success' : 'login_failed',
        'description' => $success 
            ? "User logged in successfully" 
            : "Failed login attempt: " . ($reason ?? 'Invalid credentials'),
        'severity' => $success ? 'low' : 'medium',
        'status' => $success ? 'success' : 'failed',
        'metadata' => [
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ]
    ]);
}
    /**
     * Show single audit log details
     */
    public function show($id)
    {
        try {
            $log = AuditLog::findOrFail($id);

            // Log admin viewing specific audit log
            AuditLogger::logAdminAction('view_audit_log_detail', "Admin viewed audit log #{$id}");

            return view('admin.audit-log-detail', compact('log'));

        } catch (Exception $e) {
            Log::error('Error loading audit log detail', [
                'log_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('audit.logs')
                ->with('error', 'Audit log not found');
        }
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request)
    {
        try {
            // Log export action
            AuditLogger::logAdminAction('export_audit_logs', 'Admin exported audit logs to CSV');

            $eventType = $request->input('event_type');
            $severity = $request->input('severity');
            $status = $request->input('status');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $logs = AuditLog::query()
                ->when($eventType, fn($q) => $q->where('event_type', $eventType))
                ->when($severity, fn($q) => $q->where('severity', $severity))
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->orderBy('created_at', 'desc')
                ->get();

            $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($logs) {
                $file = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($file, [
                    'ID', 'Date/Time', 'User', 'Event Type', 'Action', 
                    'Description', 'IP Address', 'Browser', 'Platform', 
                    'Severity', 'Status'
                ]);

                // CSV Data
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user_name ?? 'Guest',
                        $log->event_type,
                        $log->action,
                        $log->description,
                        $log->ip_address,
                        $log->browser,
                        $log->platform,
                        $log->severity,
                        $log->status
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            Log::error('Error exporting audit logs', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to export audit logs');
        }
    }

    /**
     * Get statistics via API
     */
    public function statistics(Request $request)
    {
        try {
            $days = $request->input('days', 7);

            $stats = [
                'overview' => [
                    'total_logs' => AuditLog::count(),
                    'today' => AuditLog::whereDate('created_at', today())->count(),
                    'this_week' => AuditLog::where('created_at', '>=', now()->subDays(7))->count(),
                ],
                'authentication' => [
                    'successful_logins' => AuditLog::where('action', 'login_success')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'failed_logins' => AuditLog::where('action', 'login_failed')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'mfa_success' => AuditLog::where('action', 'LIKE', 'mfa_%')
                        ->where('status', 'success')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                ],
                'security' => [
                    'suspicious_activities' => AuditLog::where('status', 'suspicious')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'blocked_attempts' => AuditLog::where('status', 'blocked')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'critical_events' => AuditLog::where('severity', 'critical')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                ],
                'by_severity' => [
                    'low' => AuditLog::where('severity', 'low')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'medium' => AuditLog::where('severity', 'medium')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'high' => AuditLog::where('severity', 'high')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                    'critical' => AuditLog::where('severity', 'critical')
                        ->where('created_at', '>=', now()->subDays($days))->count(),
                ],
                'by_event_type' => AuditLog::selectRaw('event_type, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('event_type')
                    ->get()
                    ->pluck('count', 'event_type'),
            ];

            return response()->json([
                'status' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error getting audit log statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Clean old audit logs
     */
    public function clean(Request $request)
    {
        try {
            $days = $request->input('days', 90);

            // Log the cleanup action
            AuditLogger::logAdminAction('clean_audit_logs', "Admin initiated cleanup of logs older than {$days} days");

            AuditLogger::cleanOldLogs($days);

            return redirect()->back()->with('success', "Audit logs older than {$days} days have been cleaned");

        } catch (Exception $e) {
            Log::error('Error cleaning audit logs', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to clean audit logs');
        }
    }
}