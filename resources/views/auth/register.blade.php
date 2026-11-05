@extends('layouts.app')

@section('title', 'Register - ITDMS')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header text-center bg-primary text-white py-3">
                    <h4 class="mb-1 fw-bold">{{ __('ITDMS User Enrollment') }}</h4>
                    <small class="text-white-50">{{ __('Insider Threat Detection & Mitigation System') }}</small>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">{{ __('Full Name') }}</label>
                            <input id="name" type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="e.g. John Doe"
                                required autofocus>

                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- User ID -->
                        <div class="mb-3">
                            <label for="user_id" class="form-label fw-semibold">{{ __('User ID') }}</label>
                            <input id="user_id" type="text"
                                class="form-control @error('user_id') is-invalid @enderror"
                                name="user_id"
                                value="{{ old('user_id') }}"
                                placeholder="Choose a unique ID (e.g. staff123)"
                                required>

                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email Address -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">{{ __('Email Address') }}</label>
                            <input id="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="name@company.com"
                                required>

                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                placeholder="Create a strong password"
                                required autocomplete="new-password">

                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm Password') }}</label>
                            <input id="password_confirmation" type="password"
                                class="form-control"
                                name="password_confirmation"
                                placeholder="Re-enter your password"
                                required autocomplete="new-password">
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                {{ __('Complete Registration') }}
                            </button>
                        </div>

                        <!-- Link to Login -->
                        <div class="mt-3 text-center">
                            <small class="text-muted">Already registered? 
                                <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Log In here</a>
                            </small>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center text-muted py-3 bg-light border-0">
                    <small>&copy; {{ date('Y') }} ITDMS. User registrations are audited under security compliance.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection