@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Audit Logs</h1>
        <div>
            <a href="{{ route('audit.logs.export', request()->all()) }}" class="btn btn-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Logs</h6>
                    <h3>{{ number_format($stats['total'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Today's Activities</h6>
                    <h3>{{ number_format($stats['today'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-warning">Suspicious (7 days)</h6>
                    <h3>{{ number_format($stats['suspicious'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-danger">Critical Events (7 days)</h6>
                    <h3>{{ number_format($stats['critical'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('audit.logs') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="User, action, IP..."
                               value="{{ request('search') }}">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Event Type</label>
                        <select name="event_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($eventTypes ?? [] as $type)
                                <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="">All Severities</option>
                            <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="suspicious" {{ request('status') == 'suspicious' ? 'selected' : '' }}>Suspicious</option>
                            <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   value="{{ request('date_from') }}">
                            <span class="input-group-text">to</span>
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control" 
                                   value="{{ request('date_to') }}">
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('audit.logs') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                        <div class="float-end">
                            <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 per page</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 per page</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 per page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Audit Logs Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Event Type</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Device</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="{{ $log->severity == 'critical' ? 'table-danger' : ($log->status == 'suspicious' ? 'table-warning' : '') }}">
                                <td>{{ $log->id }}</td>
                                <td>
                                    <small>{{ $log->created_at->format('Y-m-d') }}</small><br>
                                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    {{ $log->user_name ?? 'Guest' }}
                                    @if($log->user_email)
                                        <br><small class="text-muted">{{ $log->user_email }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $log->event_type }}</span>
                                </td>
                                <td>
                                    <small>{{ $log->action }}</small>
                                </td>
                                <td>
                                    <small>{{ Str::limit($log->description, 50) }}</small>
                                </td>
                                <td>
                                    <small class="font-monospace">{{ $log->ip_address }}</small>
                                </td>
                                <td>
                                    @if($log->device_type)
                                        <i class="bi bi-{{ $log->device_type == 'mobile' ? 'phone' : ($log->device_type == 'tablet' ? 'tablet' : 'laptop') }}"></i>
                                    @endif
                                    <small>{{ $log->browser }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->severity_color }}">
                                        {{ ucfirst($log->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->status_color }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('audit.logs.show', $log->id) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="text-muted mt-2">No audit logs found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-4">
                {{ $logs->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.font-monospace {
    font-family: 'Courier New', monospace;
}
</style>
@endsection