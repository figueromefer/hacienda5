<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventTaskController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $data = $this->validatedTask($request);

        $event->tasks()->create($data);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Tarea agregada correctamente.');
    }

    public function edit(Request $request, EventTask $eventTask)
    {
        $this->authorizeTask($request, $eventTask);

        return view('event-tasks.edit', [
            'eventTask' => $eventTask->load('event'),
            'users' => User::assignableToEventTasks()->orderBy('name')->get(),
            'canReassign' => $request->user()->can('manage events'),
            'returnToDashboard' => $request->string('origin')->toString() === 'dashboard',
        ]);
    }

    public function update(Request $request, EventTask $eventTask)
    {
        $this->authorizeTask($request, $eventTask);
        $data = $this->validatedTask($request);

        if (! $request->user()->can('manage events')) {
            $data['assigned_to'] = $eventTask->assigned_to;
        }

        $eventTask->update($data);

        $route = $request->string('origin')->toString() === 'dashboard'
            ? route('dashboard')
            : route('events.show', $eventTask->event_id);

        return redirect($route)
            ->with('success', 'Tarea actualizada correctamente.');
    }

    public function complete(Request $request, EventTask $eventTask)
    {
        return $this->transition($request, $eventTask, EventTask::STATUS_DONE, 'Tarea completada correctamente.');
    }

    public function cancel(Request $request, EventTask $eventTask)
    {
        return $this->transition($request, $eventTask, EventTask::STATUS_CANCELLED, 'Tarea cancelada correctamente.');
    }

    private function transition(Request $request, EventTask $eventTask, string $status, string $message)
    {
        $this->authorizeTask($request, $eventTask);
        $eventTask->update(['status' => $status]);

        return back()->with('success', $message);
    }

    private function validatedTask(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(EventTask::STATUSES)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if (
            filled($data['assigned_to'] ?? null)
            && ! User::assignableToEventTasks()->whereKey($data['assigned_to'])->exists()
        ) {
            throw ValidationException::withMessages([
                'assigned_to' => 'El responsable debe ser un usuario interno activo.',
            ]);
        }

        return $data;
    }

    private function authorizeTask(Request $request, EventTask $eventTask): void
    {
        abort_unless(
            $request->user()->can('manage events')
            || $eventTask->assigned_to === $request->user()->id,
            403,
        );
    }
}
