<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::all();
        $events = Event::all();
        return view('admin.dashboard', compact('users', 'events'));
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function destroyEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return redirect()->back()->with('success', 'Event deleted successfully.');
    }
}
