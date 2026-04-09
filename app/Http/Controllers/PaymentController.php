<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Quotation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['client', 'event', 'quotation'])
            ->latest('payment_date')
            ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        return view('payments.create', [
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::orderBy('event_date')->get(),
            'quotations' => Quotation::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:transfer,cash,card,other'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create($data);

        return redirect()->route('payments.index')->with('success', 'Pago registrado correctamente.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['client', 'event', 'quotation']);
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        return view('payments.edit', [
            'payment' => $payment,
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::orderBy('event_date')->get(),
            'quotations' => Quotation::latest()->get(),
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:transfer,cash,card,other'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment->update($data);

        return redirect()->route('payments.index')->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Pago eliminado correctamente.');
    }
}