<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogger;

class AdminController extends Controller
{
    /**
     * Display admin dashboard with search functionality
     */
    public function index(Request $request)
    {
        try {
            AuditLogger::logAdminAction('view_admin_dashboard', 'Admin viewed dashboard');
            // Get search parameters
            $userSearch = $request->input('user_search');
            $eventSearch = $request->input('event_search');
            $userStatus = $request->input('user_status');
            $eventStatus = $request->input('event_status');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            // Query Users with search
            $users = User::query()
                ->when($userSearch, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('user_id', 'LIKE', "%{$search}%");
                    });
                })
                ->when($userStatus !== null, function ($query) use ($userStatus) {
                    if ($userStatus === 'active') {
                        return $query->where('is_active', true);
                    } elseif ($userStatus === 'inactive') {
                        return $query->where('is_active', false);
                    } elseif ($userStatus === 'locked') {
                        return $query->where('is_locked', true);
                    }
                    return $query;
                })
                ->when($dateFrom, function ($query, $date) {
                    return $query->whereDate('created_at', '>=', $date);
                })
                ->when($dateTo, function ($query, $date) {
                    return $query->whereDate('created_at', '<=', $date);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10, ['*'], 'users_page');

            // Query Events with search
            $events = Event::query()
                ->when($eventSearch, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%")
                          ->orWhere('description', 'LIKE', "%{$search}%")
                          ->orWhere('location', 'LIKE', "%{$search}%");
                    });
                })
                ->when($eventStatus !== null, function ($query) use ($eventStatus) {
                    if ($eventStatus === 'upcoming') {
                        return $query->where('event_date', '>', now());
                    } elseif ($eventStatus === 'past') {
                        return $query->where('event_date', '<', now());
                    } elseif ($eventStatus === 'today') {
                        return $query->whereDate('event_date', today());
                    }
                    return $query;
                })
                ->when($dateFrom, function ($query, $date) {
                    return $query->whereDate('event_date', '>=', $date);
                })
                ->when($dateTo, function ($query, $date) {
                    return $query->whereDate('event_date', '<=', $date);
                })
                ->orderBy('event_date', 'desc')
                ->paginate(10, ['*'], 'events_page');

            // Count statistics
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'locked_users' => User::where('is_locked', true)->count(),
                'total_events' => Event::count(),
                'upcoming_events' => Event::where('event_date', '>', now())->count(),
                'past_events' => Event::where('event_date', '<', now())->count(),
            ];

            return view('admin.dashboard', compact('users', 'events', 'stats'));

        } catch (Exception $e) {
            Log::error('Admin dashboard error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('admin.dashboard')
                ->with('error', 'Failed to load dashboard data')
                ->with('users', collect())
                ->with('events', collect())
                ->with('stats', []);
        }
    }

    /**
     * Search users via API
     */
    public function searchUsers(Request $request)
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive,locked,all',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'per_page' => 'nullable|integer|min:5|max:100'
            ]);

            $search = $request->input('search');
            $status = $request->input('status');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $perPage = $request->input('per_page', 10);

            $users = User::query()
                ->when($search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('user_id', 'LIKE', "%{$search}%");
                    });
                })
                ->when($status && $status !== 'all', function ($query) use ($status) {
                    if ($status === 'active') {
                        return $query->where('is_active', true)->where('is_locked', false);
                    } elseif ($status === 'inactive') {
                        return $query->where('is_active', false);
                    } elseif ($status === 'locked') {
                        return $query->where('is_locked', true);
                    }
                    return $query;
                })
                ->when($dateFrom, function ($query, $date) {
                    return $query->whereDate('created_at', '>=', $date);
                })
                ->when($dateTo, function ($query, $date) {
                    return $query->whereDate('created_at', '<=', $date);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem(),
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Search users error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search users'
            ], 500);
        }
    }

    /**
     * Search events via API
     */
    public function searchEvents(Request $request)
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:upcoming,past,today,all',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'per_page' => 'nullable|integer|min:5|max:100'
            ]);

            $search = $request->input('search');
            $status = $request->input('status');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $perPage = $request->input('per_page', 10);

            $events = Event::query()
                ->when($search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%")
                          ->orWhere('description', 'LIKE', "%{$search}%")
                          ->orWhere('location', 'LIKE', "%{$search}%");
                    });
                })
                ->when($status && $status !== 'all', function ($query) use ($status) {
                    if ($status === 'upcoming') {
                        return $query->where('event_date', '>', now());
                    } elseif ($status === 'past') {
                        return $query->where('event_date', '<', now());
                    } elseif ($status === 'today') {
                        return $query->whereDate('event_date', today());
                    }
                    return $query;
                })
                ->when($dateFrom, function ($query, $date) {
                    return $query->whereDate('event_date', '>=', $date);
                })
                ->when($dateTo, function ($query, $date) {
                    return $query->whereDate('event_date', '<=', $date);
                })
                ->orderBy('event_date', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Events retrieved successfully',
                'data' => [
                    'events' => $events->items(),
                    'pagination' => [
                        'current_page' => $events->currentPage(),
                        'last_page' => $events->lastPage(),
                        'per_page' => $events->perPage(),
                        'total' => $events->total(),
                        'from' => $events->firstItem(),
                        'to' => $events->lastItem(),
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Search events error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search events'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->where('is_locked', false)->count(),
                    'inactive' => User::where('is_active', false)->count(),
                    'locked' => User::where('is_locked', true)->count(),
                    'new_today' => User::whereDate('created_at', today())->count(),
                    'new_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                ],
                'events' => [
                    'total' => Event::count(),
                    'upcoming' => Event::where('event_date', '>', now())->count(),
                    'past' => Event::where('event_date', '<', now())->count(),
                    'today' => Event::whereDate('event_date', today())->count(),
                    'this_week' => Event::whereBetween('event_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => Event::whereMonth('event_date', now()->month)->count(),
                ]
            ];

            return response()->json([
                'status' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ], 200);

        } catch (Exception $e) {
            Log::error('Get stats error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Deactivate user (instead of delete)
     */
    public function deactivateUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deactivating yourself
            if (auth()->id() === $user->id) {
                return redirect()->back()->with('error', 'You cannot deactivate your own account.');
            }

            // Check if already inactive
            if (!$user->is_active) {
                return redirect()->back()->with('info', "User '{$user->name}' is already inactive.");
            }

            $userName = $user->name;
            
            // Deactivate user
            $user->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_by' => auth()->id()
            ]);

            Log::info('User deactivated', [
                'user_id' => $id,
                'user_name' => $userName,
                'deactivated_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "User '{$userName}' has been deactivated successfully.");

        } catch (Exception $e) {
            Log::error('Deactivate user error', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to deactivate user. Please try again.');
        }
    }

    /**
     * Activate user (reactivate)
     */
    public function activateUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if already active
            if ($user->is_active) {
                return redirect()->back()->with('info', "User '{$user->name}' is already active.");
            }

            $userName = $user->name;
            
            // Activate user
            $user->update([
                'is_active' => true,
                'deactivated_at' => null,
                'deactivated_by' => null,
                'activated_at' => now(),
                'activated_by' => auth()->id()
            ]);

            Log::info('User activated', [
                'user_id' => $id,
                'user_name' => $userName,
                'activated_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "User '{$userName}' has been activated successfully.");

        } catch (Exception $e) {
            Log::error('Activate user error', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to activate user. Please try again.');
        }
    }

    /**
     * Delete user permanently (optional - admin only)
     */
    public function destroyUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting yourself
            if (auth()->id() === $user->id) {
                return redirect()->back()->with('error', 'You cannot delete your own account.');
            }

            $userName = $user->name;
            $user->delete();

            Log::warning('User permanently deleted', [
                'deleted_user_id' => $id,
                'deleted_user_name' => $userName,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "User '{$userName}' deleted permanently.");

        } catch (Exception $e) {
            Log::error('Delete user error', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to delete user. Please try again.');
        }
    }

    /**
     * Delete event with proper error handling
     */
    public function destroyEvent($id)
    {
        try {
            $event = Event::findOrFail($id);
            $eventTitle = $event->title;
            $event->delete();

            Log::info('Event deleted', [
                'event_id' => $id,
                'event_title' => $eventTitle,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "Event '{$eventTitle}' deleted successfully.");

        } catch (Exception $e) {
            Log::error('Delete event error', [
                'event_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to delete event. Please try again.');
        }
    }

    /**
     * Export users to CSV
     */
    public function exportUsers(Request $request)
    {
        try {
             AuditLogger::logAdminAction('export_users', 'Admin exported users to CSV', [
                'filters' => $request->only(['search', 'status'])
            ]);
            $search = $request->input('search');
            $status = $request->input('status');

            $users = User::query()
                ->when($search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('user_id', 'LIKE', "%{$search}%");
                    });
                })
                ->when($status && $status !== 'all', function ($query) use ($status) {
                    if ($status === 'active') {
                        return $query->where('is_active', true);
                    } elseif ($status === 'inactive') {
                        return $query->where('is_active', false);
                    } elseif ($status === 'locked') {
                        return $query->where('is_locked', true);
                    }
                    return $query;
                })
                ->get();

            $filename = 'users_' . date('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($users) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'User ID', 'Name', 'Email', 'Status', 'Created At']);

                foreach ($users as $user) {
                    $status = $user->is_locked ? 'Locked' : ($user->is_active ? 'Active' : 'Inactive');
                    fputcsv($file, [
                        $user->id,
                        $user->user_id,
                        $user->name,
                        $user->email,
                        $status,
                        $user->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            Log::error('Export users error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to export users.');
        }
    }

    /**
     * Export events to CSV
     */
    public function exportEvents(Request $request)
    {
        try {
            AuditLogger::logAdminAction('export_events', 'Admin exported events to CSV', [
                'filters' => $request->only(['search', 'status'])
            ]);
            $search = $request->input('search');
            $status = $request->input('status');

            $events = Event::query()
                ->when($search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%")
                          ->orWhere('description', 'LIKE', "%{$search}%");
                    });
                })
                ->when($status && $status !== 'all', function ($query) use ($status) {
                    if ($status === 'upcoming') {
                        return $query->where('event_date', '>', now());
                    } elseif ($status === 'past') {
                        return $query->where('event_date', '<', now());
                    }
                    return $query;
                })
                ->get();

            $filename = 'events_' . date('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($events) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Title', 'Description', 'Location', 'Event Date', 'Created At']);

                foreach ($events as $event) {
                    fputcsv($file, [
                        $event->id,
                        $event->title,
                        $event->description,
                        $event->location,
                        $event->event_date,
                        $event->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            Log::error('Export events error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to export events.');
        }
    }
}