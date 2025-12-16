@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header text-center bg-primary text-white">
                    <h4>{{ __('Register MyEventSystem Account') }}</h4>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Name -->
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                name="name"
                                value="{{ old('name') }}"
                                required>

                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- User ID -->
                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <input type="text"
                                class="form-control @error('user_id') is-invalid @enderror"
                                name="user_id"
                                value="{{ old('user_id') }}"
                                required>

                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email') }}"
                                required>

                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                required>

                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password"
                                class="form-control"
                                name="password_confirmation"
                                required>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary">
                                Register
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center text-muted">
                    © 2025 MyEventSystem
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
