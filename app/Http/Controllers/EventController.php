<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Display events for normal user or admin
    public function index()
    {
        if (Auth::user()->role == 'admin') {
            $events = Event::all();
        } else {
            $events = Event::where('user_id', Auth::id())->get();
        }

        return view('events.index', compact('events'));
    }

    public function create()
    {
        return view('events.create');
    }

    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'event_date'  => 'required|date',
        ]);

        // Create event with mass assignment
        $event = Event::create([
            'title'       => $request->title,
            'description' => $request->description,
            'event_date'  => $request->event_date,
            'user_id'    => Auth::id(),
        ]);

        return redirect()->route('events.index')
                         ->with('success', 'Event created successfully!');
    }

    // Show edit event form
    public function edit(Event $event)
    {
        return view('events.edit', compact('event'));
    }

    // Update event
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'event_date'  => 'required|date',
        ]);

        $event->update([
            'title'       => $request->title,
            'description' => $request->description,
            'event_date'  => $request->event_date,
        ]);

        return redirect()->route('events.index')
                         ->with('success', 'Event updated successfully!');
    }

    // Delete event
    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('events.index')
                         ->with('success', 'Event deleted successfully!');
    }
}
