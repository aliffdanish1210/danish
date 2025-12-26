@extends('layouts.app')

@section('title', 'Edit Event')

@section('content')
<div class="card">
    <div class="card-header">Edit Event</div>

    <div class="card-body">
        <form action="{{ route('events.update', $event->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text"
                       name="title"
                       class="form-control"
                       value="{{ old('title', $event->title) }}"
                       required>
                @error('title')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description"
                          class="form-control"
                          required>{{ old('description', $event->description) }}</textarea>
                @error('description')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Event Date -->
            <div class="mb-3">
                <label class="form-label">Event Date</label>
                <input type="date"
                       name="event_date"
                       class="form-control"
                       value="{{ old('event_date', $event->event_date) }}"
                       required>
                @error('event_date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <button class="btn btn-primary">Update Event</button>
        </form>
    </div>
</div>
@endsection
