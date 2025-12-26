<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'MyEventSystem')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('events.index') }}">
                {{ config('app.name', 'MyEventSystem') }}
            </a>

            <!-- Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left -->
                 <ul class="navbar-nav me-auto">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('events.index') }}">Events</a>
                        </li>

                        {{-- ADMIN MENU (ONLY ADMIN CAN SEE) --}}
                        @role('admin')
                            <li class="nav-item">
                                <a class="nav-link fw-bold text-warning"
                                   href="{{ route('admin.dashboard') }}">
                                    Admin Panel
                                </a>
                            </li>
                        @endrole
                    @endauth
                </ul>

                <!-- Right -->
                <ul class="navbar-nav ms-auto">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#"
                               role="button" data-bs-toggle="dropdown">
                                {{ Auth::user()->name }}
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        Profile
                                    </a>
                                </li>

                                <li><hr class="dropdown-divider"></li>

                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                       document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>
                                </li>
                            </ul>

                            <!-- REAL logout -->
                            <form id="logout-form" action="{{ route('logout') }}"
                                  method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
