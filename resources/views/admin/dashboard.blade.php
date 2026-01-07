@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Admin Dashboard</h1>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2>{{ $stats['total_users'] ?? 0 }}</h2>
                    <small>{{ $stats['active_users'] ?? 0 }} Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Users</h5>
                    <h2>{{ $stats['active_users'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Events</h5>
                    <h2>{{ $stats['total_events'] ?? 0 }}</h2>
                    <small>{{ $stats['upcoming_events'] ?? 0 }} Upcoming</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Locked Users</h5>
                    <h2>{{ $stats['locked_users'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Users Section --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="mb-0">Users Management</h3>
        </div>
        <div class="card-body">
            {{-- Search Panel for Users --}}
            <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               name="user_search" 
                               class="form-control" 
                               placeholder="Search by name, email, or user ID..."
                               value="{{ request('user_search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="user_status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" {{ request('user_status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('user_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="locked" {{ request('user_status') == 'locked' ? 'selected' : '' }}>Locked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               placeholder="From Date"
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               placeholder="To Date"
                               value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                        @if(Route::has('users.export'))
                            <a href="{{ route('users.export', request()->all()) }}" class="btn btn-success btn-sm">
                                <i class="bi bi-download"></i> Export CSV
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Users Table --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->user_id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->is_locked)
                                        <span class="badge bg-danger">Locked</span>
                                    @elseif($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if($user->is_active)
                                        {{-- Deactivate button for active users --}}
                                        <form action="{{ route('users.deactivate', $user->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="bi bi-toggle-off"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        {{-- Activate button for inactive users --}}
                                        <form action="{{ route('users.activate', $user->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to activate this user?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="bi bi-toggle-on"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- Optional: Delete button (permanent) - for super admin only --}}
                                    @if(auth()->user()->hasRole('super-admin'))
                                        <form action="{{ route('users.destroy', $user->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('WARNING: This will permanently delete the user. Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination for Users --}}
            <div class="d-flex justify-content-center">
                {{ $users->appends(request()->except('events_page'))->links() }}
            </div>
        </div>
    </div>

    {{-- Events Section --}}
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Events Management</h3>
        </div>
        <div class="card-body">
            {{-- Search Panel for Events --}}
            <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-3">
                {{-- Keep user search parameters --}}
                @if(request('user_search'))
                    <input type="hidden" name="user_search" value="{{ request('user_search') }}">
                @endif
                @if(request('user_status'))
                    <input type="hidden" name="user_status" value="{{ request('user_status') }}">
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               name="event_search" 
                               class="form-control" 
                               placeholder="Search by title, description, or location..."
                               value="{{ request('event_search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="event_status" class="form-select">
                            <option value="">All Events</option>
                            <option value="upcoming" {{ request('event_status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="today" {{ request('event_status') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="past" {{ request('event_status') == 'past' ? 'selected' : '' }}>Past</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               placeholder="From Date"
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               placeholder="To Date"
                               value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                        @if(Route::has('events.export'))
                            <a href="{{ route('events.export', request()->all()) }}" class="btn btn-success btn-sm">
                                <i class="bi bi-download"></i> Export CSV
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Events Table --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Location</th>
                            <th>Event Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>{{ $event->id }}</td>
                                <td>{{ $event->title }}</td>
                                <td>{{ Str::limit($event->description, 50) }}</td>
                                <td>{{ $event->location }}</td>
                                <td>{{ \Carbon\Carbon::parse($event->event_date)->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if(\Carbon\Carbon::parse($event->event_date)->isToday())
                                        <span class="badge bg-info">Today</span>
                                    @elseif(\Carbon\Carbon::parse($event->event_date)->isFuture())
                                        <span class="badge bg-success">Upcoming</span>
                                    @else
                                        <span class="badge bg-secondary">Past</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('events.destroy', $event->id) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this event?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No events found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination for Events --}}
            <div class="d-flex justify-content-center">
                {{ $events->appends(request()->except('users_page'))->links() }}
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    border-radius: 0.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 1.25rem;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    padding: 0.35em 0.65em;
    font-size: 0.875em;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
@endsection