@extends('layouts.app')

@section('title', 'Events')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Events</h2>
    <a href="{{ route('events.create') }}" class="btn btn-success">Create Event</a>
</div>

@if($events->count())
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Event Date</th>
            <th>Owner</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($events as $event)
        <tr>
            <td>{{ $event->title }}</td>
            <td>{{ $event->description }}</td>
            <td>{{ $event->event_date }}</td>
            <td>{{ $event->user->name }}</td>
            <td>
                <a href="{{ route('events.edit', $event->id) }}" class="btn btn-primary btn-sm">Edit</a>
                <form action="{{ route('events.destroy', $event->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p>No events found.</p>
@endif
@endsection
