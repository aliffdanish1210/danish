@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p>{{ __('You are logged in!') }}</p>
                    
                    <h5>Profile Info</h5>
                    <ul>
                        <li><strong>User ID:</strong> {{ auth()->user()->user_id }}</li>
                        <li><strong>Name:</strong> {{ auth()->user()->name ?? 'N/A' }}</li>
                        <li><strong>Email:</strong> {{ auth()->user()->email ?? 'N/A' }}</li>
                    </ul>

                    <h5>Roles & Permissions</h5>
                    <ul>
                        @foreach (auth()->user()->getRoleNames() as $role)
                            <li>{{ $role }}</li>
                        @endforeach
                    </ul>

                    @if(auth()->user()->hasRole('Admin'))
                        <h5>Admin Tools</h5>
                        <ul>
                            <li><a href="{{ route('auditLogs.index') }}">View Audit Logs</a></li>
                            <li><a href="{{ route('events.index') }}">Manage Events</a></li>
                            <li><a href="{{ route('users.index') }}">Manage Users</a></li>
                        </ul>
                    @endif

                    <h5>Modules</h5>
                    <ul>
                        <li><a href="{{ route('events.register') }}">Event Registration</a></li>
                        <li><a href="{{ route('profile.show') }}">User Profile</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
