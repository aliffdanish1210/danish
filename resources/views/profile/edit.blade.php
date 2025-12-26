@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="card">
    <div class="card-header">Edit Profile</div>
    <div class="card-body">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                @error('name')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                @error('email')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Password (Leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
                @error('password')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>

            <button class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>
@endsection
