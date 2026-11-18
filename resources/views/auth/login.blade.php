@extends('layouts.app')

@section('title', 'Login - ITDMS')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header text-center bg-primary text-white py-3">
                    <h4 class="mb-1 fw-bold">{{ __('ITDMS Security Portal') }}</h4>
                    <small class="text-white-50">{{ __('Insider Threat Detection & Mitigation System') }}</small>
                </div>

                <div class="card-body p-4">
                    {{-- Alert banner for automated lockouts / security violations --}}
                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Success message, e.g. after registration --}}
                    @if (session('success') || session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') ?? session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- User ID -->
                        <div class="mb-3">
                            <label for="user_id" class="form-label fw-semibold">{{ __('User ID') }}</label>
                            <input id="user_id" type="text"
                                class="form-control @error('user_id') is-invalid @enderror"
                                name="user_id"
                                value="{{ old('user_id') }}"
                                placeholder="Enter your system User ID"
                                required autofocus>

                            @error('user_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                            <div class="input-group">
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                placeholder="Enter your password"
                                required autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary" data-toggle-password="#password" aria-label="Show password" aria-pressed="false">Show</button>
                            </div>

                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember Me -->
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                {{ __('Remember Me') }}
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                 @csrf
                                {{ __('Login') }}
                            </button>
                        </div>

                        <!-- Forgot Password -->
                        @if (Route::has('password.request'))
                            <div class="mt-3 text-center">
                                <a class="text-decoration-none small" href="{{ route('password.request') }}">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            </div>
                        @endif
                    </form>
                </div>

                <div class="card-footer text-center text-muted py-3 bg-light border-0">
                    <small>&copy; {{ date('Y') }} ITDMS. All security events and access are monitored.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show / hide password buttons
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.querySelector(btn.getAttribute('data-toggle-password'));
            if (!input) { return; }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });
</script>
@endsection