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
        $documents = Document::with(['client', 'event', 'uploader'])
            ->latest()
            ->paginate(20);

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        return view('documents.create', [
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::orderBy('event_date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'category' => ['required', 'in:contract,receipt,identification,voucher,other'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'notes' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'client_id' => $data['client_id'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'uploaded_by' => $request->user()?->id,
            'category' => $data['category'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('documents.index')->with('success', 'Documento subido correctamente.');
    }

    public function show(Document $document)
    {
        $document->load(['client', 'event', 'uploader']);
        return view('documents.show', compact('document'));
    }

    public function destroy(Document $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Documento eliminado correctamente.');
    }
}