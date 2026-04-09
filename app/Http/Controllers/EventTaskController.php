<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Http\Request;

class EventTaskController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,done,cancelled'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $event->tasks()->create($data);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Tarea agregada correctamente.');
    }

    public function destroy(EventTask $eventTask)
    {
        $eventId = $eventTask->event_id;
        $eventTask->delete();

        return redirect()
            ->route('events.show', $eventId)
            ->with('success', 'Tarea eliminada correctamente.');
    }
}