<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Service;
use App\Support\DomainLabels;
use App\Support\MoneyNormalizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $matchingStatuses = collect(DomainLabels::QUOTATION_STATUSES)
            ->filter(fn (string $label, string $status) => str_contains(Str::lower($label), Str::lower($search))
                || str_contains(Str::lower($status), Str::lower($search)))
            ->keys();

        $quotations = Quotation::query()
            ->with(['client', 'event'])
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($search !== '', function ($query) use ($matchingStatuses, $search) {
                $query->where(function ($query) use ($matchingStatuses, $search) {
                    $query->where('folio', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($client) => $client->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('event', fn ($event) => $event->where('title', 'like', "%{$search}%"));

                    if ($matchingStatuses->isNotEmpty()) {
                        $query->orWhereIn('status', $matchingStatuses);
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        return view('quotations.create', [
            'clients' => Client::orderBy('full_name')->get(),
            'events' => $this->quotationEvents(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(MoneyNormalizer::normalizeArray($request->all(), [
            'discount',
            'items.*.unit_price',
        ]));

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => [
                'nullable',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('client_id', $request->input('client_id'))),
            ],
            'status' => ['required', 'in:draft,sent,approved,rejected,expired'],
            'valid_until' => ['nullable', 'date'],
            'discount_type' => ['nullable', Rule::in(['amount', 'percentage'])],
            'discount' => ['nullable', 'numeric', 'min:0', Rule::when($request->input('discount_type') === 'percentage', ['max:100'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            [$items, $subtotal, $discount, $total] = $this->calculateTotals($data);

            $quotation = Quotation::create([
                'client_id' => $data['client_id'],
                'event_id' => $data['event_id'] ?? null,
                'folio' => null,
                'status' => $data['status'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_type' => $data['discount_type'] ?? 'amount',
                'total' => $total,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $quotation->update([
                'folio' => 'C-'.str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($items as $item) {
                $quotation->items()->create($item);
            }
        });

        return redirect()->route('quotations.index')->with('success', 'Cotización creada correctamente.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'event', 'items.service']);

        return view('quotations.show', compact('quotation'));
    }

    public function pdf(Quotation $quotation)
    {
        $quotation->load(['client', 'event', 'items.service']);

        $logoPath = public_path('images/hacienda-cinco-logo.png');
        $pdf = Pdf::loadView('quotations.pdf', compact('quotation', 'logoPath'))
            ->setPaper('letter');

        $reference = Str::slug($quotation->folio ?: (string) $quotation->id);

        return $pdf->download("cotizacion-{$reference}.pdf");
    }

    public function edit(Quotation $quotation)
    {
        $quotation->load('items');

        return view('quotations.edit', [
            'quotation' => $quotation,
            'clients' => Client::orderBy('full_name')->get(),
            'events' => $this->quotationEvents(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $request->merge(MoneyNormalizer::normalizeArray($request->all(), [
            'discount',
            'items.*.unit_price',
        ]));

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => [
                'nullable',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('client_id', $request->input('client_id'))),
            ],
            'status' => ['required', 'in:draft,sent,approved,rejected,expired'],
            'valid_until' => ['nullable', 'date'],
            'discount_type' => ['nullable', Rule::in(['amount', 'percentage'])],
            'discount' => ['nullable', 'numeric', 'min:0', Rule::when($request->input('discount_type') === 'percentage', ['max:100'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $quotation) {
            [$items, $subtotal, $discount, $total] = $this->calculateTotals($data);

            $quotation->update([
                'client_id' => $data['client_id'],
                'event_id' => $data['event_id'] ?? null,
                'status' => $data['status'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_type' => $data['discount_type'] ?? 'amount',
                'total' => $total,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $quotation->items()->delete();

            foreach ($items as $item) {
                $quotation->items()->create($item);
            }
        });

        return redirect()->route('quotations.index')->with('success', 'Cotización actualizada correctamente.');
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DomainLabels::QUOTATION_STATUSES))],
        ]);

        if ($data['status'] === 'approved' && ! $quotation->items()->exists()) {
            return back()->withErrors(['status' => 'Una cotización sin partidas no puede aprobarse.']);
        }

        $quotation->update(['status' => $data['status']]);

        return back()->with('success', 'Estatus de cotización actualizado.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Cotización eliminada correctamente.');
    }

    private function quotationEvents()
    {
        return Event::query()
            ->select(['id', 'client_id', 'title', 'event_date', 'event_type', 'guest_count', 'status', 'budget_estimate'])
            ->orderBy('event_date')
            ->get();
    }

    private function calculateTotals(array $data): array
    {
        $subtotal = '0.00';
        $items = [];

        foreach ($data['items'] as $item) {
            $itemTotal = bcmul((string) $item['quantity'], (string) $item['unit_price'], 2);
            $subtotal = bcadd($subtotal, $itemTotal, 2);
            $items[] = [
                'service_id' => $item['service_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $itemTotal,
            ];
        }

        $discount = bcadd('0.00', (string) ($data['discount'] ?? 0), 2);
        $effectiveDiscount = ($data['discount_type'] ?? 'amount') === 'percentage'
            ? bcdiv(bcmul($subtotal, $discount, 4), '100', 2)
            : $discount;
        $total = bccomp($subtotal, $effectiveDiscount, 2) === 1
            ? bcsub($subtotal, $effectiveDiscount, 2)
            : '0.00';

        return [$items, $subtotal, $discount, $total];
    }
}
