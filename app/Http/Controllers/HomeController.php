<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth'); // Ensure only logged-in users can access
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $user = Auth::user(); // Get the currently logged-in user

        // Example dashboard data
        $dashboardData = [
            'user_id' => $user->user_id,
            'name' => $user->name ?? 'N/A',
            'email' => $user->email ?? 'N/A',
            'roles' => $user->getRoleNames(), // if using Spatie roles
            'permissions' => $user->getAllPermissions(), // custom method
            'current_signin' => $user->current_signin,
            'last_signin' => $user->last_signin,
        ];

        return view('home', compact('dashboardData'));
    }
}
