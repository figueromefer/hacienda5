<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('client')->orderBy('event_date')->paginate(15);
        return view('events.index', compact('events'));
    }

    public function create()
    {
        return view('events.create', [
            'clients' => Client::orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:tentative,confirmed,completed,cancelled'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'guest_count' => ['nullable', 'integer', 'min:0'],
            'budget_estimate' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['total_amount'] = $data['total_amount'] ?? 0;

        Event::create($data);

        return redirect()->route('events.index')->with('success', 'Evento creado correctamente.');
    }

    public function show(Event $event)
{
    $event->load([
        'client',
        'payments',
        'documents',
        'tasks.assignedUser',
        'notesList.user',
        'quotations',
    ]);

    $users = \App\Models\User::orderBy('name')->get();

    return view('events.show', compact('event', 'users'));
}

    public function edit(Event $event)
    {
        return view('events.edit', [
            'event' => $event,
            'clients' => Client::orderBy('full_name')->get(),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:tentative,confirmed,completed,cancelled'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'guest_count' => ['nullable', 'integer', 'min:0'],
            'budget_estimate' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['total_amount'] = $data['total_amount'] ?? 0;

        $event->update($data);

        return redirect()->route('events.index')->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Evento eliminado correctamente.');
    }
}