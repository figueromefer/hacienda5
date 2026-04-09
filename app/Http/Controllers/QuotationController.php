<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::with(['client', 'event'])
            ->latest()
            ->paginate(15);

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        return view('quotations.create', [
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::orderBy('event_date')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'status' => ['required', 'in:draft,sent,approved,rejected,expired'],
            'valid_until' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $subtotal = collect($data['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $discount = $data['discount'] ?? 0;
            $total = max($subtotal - $discount, 0);

            $quotation = Quotation::create([
                'client_id' => $data['client_id'],
                'event_id' => $data['event_id'] ?? null,
                'folio' => 'COT-' . now()->format('YmdHis'),
                'status' => $data['status'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $quotation->items()->create([
                    'service_id' => $item['service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('quotations.index')->with('success', 'Cotización creada correctamente.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'event', 'items.service']);
        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $quotation->load('items');

        return view('quotations.edit', [
            'quotation' => $quotation,
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::orderBy('event_date')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'status' => ['required', 'in:draft,sent,approved,rejected,expired'],
            'valid_until' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $quotation) {
            $subtotal = collect($data['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $discount = $data['discount'] ?? 0;
            $total = max($subtotal - $discount, 0);

            $quotation->update([
                'client_id' => $data['client_id'],
                'event_id' => $data['event_id'] ?? null,
                'status' => $data['status'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $quotation->items()->delete();

            foreach ($data['items'] as $item) {
                $quotation->items()->create([
                    'service_id' => $item['service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('quotations.index')->with('success', 'Cotización actualizada correctamente.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Cotización eliminada correctamente.');
    }
}