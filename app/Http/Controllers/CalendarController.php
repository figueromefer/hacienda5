<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function feed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after:start'],
        ]);

        $query = Event::query()
            ->with('client')
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->orderBy('id');

        if (! empty($validated['start'])) {
            $query->whereDate('event_date', '>=', CarbonImmutable::parse($validated['start'])->toDateString());
        }

        if (! empty($validated['end'])) {
            // FullCalendar envía el límite final como fecha exclusiva.
            $query->whereDate('event_date', '<', CarbonImmutable::parse($validated['end'])->toDateString());
        }

        $events = $query->get()
            ->unique('id')
            ->map(fn (Event $event): array => $this->calendarEvent($event))
            ->values();

        return response()
            ->json($events)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function calendarEvent(Event $event): array
    {
        $date = $event->event_date->format('Y-m-d');
        $hasStartTime = filled($event->start_time);
        $startTime = $hasStartTime ? $this->normalizeTime($event->start_time) : null;
        $endTime = filled($event->end_time) ? $this->normalizeTime($event->end_time) : null;
        $start = $hasStartTime ? "{$date}T{$startTime}" : $date;
        $end = null;

        if ($hasStartTime && $endTime) {
            $startDateTime = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$date} {$startTime}");
            $endDateTime = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$date} {$endTime}");

            if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                $endDateTime = $endDateTime->addDay();
            }

            $end = $endDateTime->format('Y-m-d\TH:i:s');
        }

        $colors = $event->calendarColors();
        $clientName = $event->client?->full_name ?? 'Sin cliente';

        return [
            'id' => (string) $event->id,
            'title' => $event->title,
            'start' => $start,
            'end' => $end,
            'allDay' => ! $hasStartTime,
            'url' => route('events.show', $event),
            'backgroundColor' => $colors['background'],
            'borderColor' => $colors['border'],
            'textColor' => $colors['text'],
            'classNames' => $event->status === Event::STATUS_CANCELLED ? ['calendar-event-cancelled'] : [],
            'extendedProps' => [
                'client' => $clientName,
                'eventType' => $event->event_type,
                'status' => $event->status,
                'statusLabel' => $event->status_label,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ],
        ];
    }

    private function normalizeTime(string $time): string
    {
        return CarbonImmutable::parse($time)->format('H:i:s');
    }
}
