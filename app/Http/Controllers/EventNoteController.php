<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventNote;
use Illuminate\Http\Request;

class EventNoteController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $event->notesList()->create([
            'user_id' => $request->user()->id,
            'note' => $data['note'],
        ]);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Nota agregada correctamente.');
    }

    public function destroy(EventNote $eventNote)
    {
        $eventId = $eventNote->event_id;
        $eventNote->delete();

        return redirect()
            ->route('events.show', $eventId)
            ->with('success', 'Nota eliminada correctamente.');
    }
}