@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<h2 class="mb-4">Admin Dashboard</h2>

<div class="row">
    <!-- Users Section -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">All Users</div>
            <div class="card-body p-0">
                @if($users->count())
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->user_id }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td>
                                @if($user->id != auth()->id())
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Delete</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="p-3">No users found.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Events Section -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">All Events</div>
            <div class="card-body p-0">
                @if($events->count())
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Owner</th>
                            <th>Event Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->user->name }}</td>
                            <td>{{ $event->event_date }}</td>
                            <td>
                                <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this event?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="p-3">No events found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
