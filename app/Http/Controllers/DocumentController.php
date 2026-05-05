<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        return redirect()->route('events.index');
    }

    public function create(Request $request)
    {
        $event = $request->filled('event_id') ? Event::with('client')->find($request->event_id) : null;

        return view('documents.create', [
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::with('client')->orderBy('event_date')->get(),
            'selectedEvent' => $event,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'event_id' => ['required', 'exists:events,id'],
            'category' => ['required', 'in:contract,receipt,identification,voucher,other'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'notes' => ['nullable', 'string'],
        ]);

        $event = Event::with('client')->findOrFail($data['event_id']);
        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'client_id' => $data['client_id'] ?? $event->client_id,
            'event_id' => $event->id,
            'uploaded_by' => $request->user()?->id,
            'category' => $data['category'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('events.show', $event)->with('success', 'Documento subido correctamente.');
    }

    public function show(Document $document)
    {
        return redirect()->to(asset('storage/' . $document->file_path));
    }

    public function destroy(Document $document)
    {
        $eventId = $document->event_id;

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return $eventId
            ? redirect()->route('events.show', $eventId)->with('success', 'Documento eliminado correctamente.')
            : redirect()->route('events.index')->with('success', 'Documento eliminado correctamente.');
    }
}
