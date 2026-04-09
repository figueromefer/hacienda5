<?php

namespace App\Http\Controllers;

use App\Models\Event;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function feed()
    {
        $events = Event::with('client')->get()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title . ' - ' . $event->client->full_name,
                'start' => $event->event_date->format('Y-m-d'),
                'url' => route('events.show', $event),
            ];
        });

        return response()->json($events);
    }
}
