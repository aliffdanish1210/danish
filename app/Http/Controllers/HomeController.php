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

        return view('home', compact('user')); // Pass user data to the home view
    }
}
