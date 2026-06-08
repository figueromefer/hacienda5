<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Transaction;
use App\Support\SpanishMoney;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['client', 'event', 'quotation'])
            ->latest('transaction_date');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->to);
        }

        $summaryQuery = clone $query;

        $income = (clone $summaryQuery)->where('type', Transaction::TYPE_INCOME)->where('status', 'paid')->sum('amount');
        $expenses = (clone $summaryQuery)->where('type', Transaction::TYPE_EXPENSE)->where('status', 'paid')->sum('amount');
        $balance = $income - $expenses;

        return view('transactions.index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'events' => Event::orderBy('event_date')->get(),
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $balance,
        ]);
    }

    public function create(Request $request)
    {
        $event = $request->filled('event_id') ? Event::with('client')->find($request->event_id) : null;

        return view('transactions.create', [
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::with('client')->orderBy('event_date')->get(),
            'quotations' => Quotation::latest()->get(),
            'selectedType' => $request->get('type', Transaction::TYPE_INCOME),
            'selectedEvent' => $event,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'type' => ['required', 'in:income,expense'],
            'scope' => ['required', 'in:event,operation'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['scope'] === 'event' && empty($data['event_id'])) {
            return back()->withErrors(['event_id' => 'Selecciona un evento para movimientos de evento.'])->withInput();
        }

        Transaction::create($data);

        if (!empty($data['event_id'])) {
            return redirect()->route('events.show', $data['event_id'])->with('success', 'Movimiento registrado correctamente.');
        }

        return redirect()->route('transactions.index')->with('success', 'Movimiento registrado correctamente.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['client', 'event', 'quotation']);

        return view('transactions.show', $this->receiptViewData($transaction));
    }

    public function pdf(Transaction $transaction)
    {
        $transaction->load(['client', 'event', 'quotation']);

        $pdf = Pdf::loadView('transactions.receipt-pdf', $this->receiptViewData($transaction))
            ->setPaper('letter');

        $filename = 'recibo-' . $transaction->id . '-' . Str::slug($transaction->client?->full_name ?? 'movimiento') . '.pdf';

        return $pdf->download($filename);
    }

    public function edit(Transaction $transaction)
    {
        return view('transactions.edit', [
            'transaction' => $transaction,
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::with('client')->orderBy('event_date')->get(),
            'quotations' => Quotation::latest()->get(),
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'type' => ['required', 'in:income,expense'],
            'scope' => ['required', 'in:event,operation'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['scope'] === 'event' && empty($data['event_id'])) {
            return back()->withErrors(['event_id' => 'Selecciona un evento para movimientos de evento.'])->withInput();
        }

        $transaction->update($data);

        return redirect()->route('transactions.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(Transaction $transaction)
    {
        $eventId = $transaction->event_id;
        $transaction->delete();

        if ($eventId) {
            return redirect()->route('events.show', $eventId)->with('success', 'Movimiento eliminado correctamente.');
        }

        return redirect()->route('transactions.index')->with('success', 'Movimiento eliminado correctamente.');
    }

    private function receiptViewData(Transaction $transaction): array
    {
        return [
            'transaction' => $transaction,
            'receiptTitle' => $transaction->type === Transaction::TYPE_INCOME ? 'RECIBO DE ANTICIPO' : 'RECIBO PAGO TRABAJOS',
            'amountInWords' => SpanishMoney::toWords((float) $transaction->amount),
            'logoPath' => public_path('images/hacienda-cinco-logo.png'),
            'brandGreen' => '#243834',
        ];
    }
}
