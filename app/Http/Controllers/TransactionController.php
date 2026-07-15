<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\ExpenseConcept;
use App\Models\Quotation;
use App\Models\ReceiptEmailLog;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Rules\EmailList;
use App\Services\ReceiptEmailSender;
use App\Services\TransactionReferenceGenerator;
use App\Support\SpanishMoney;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['client', 'event', 'quotation', 'supplier', 'expenseConcept'])
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
        $event = $request->filled('event_id') ? Event::with('client.user')->find($request->event_id) : null;
        $clients = Client::with('user')->orderBy('full_name')->get();
        $events = Event::with('client.user')->orderBy('event_date')->get();
        $suggestedRecipients = collect([
            $event?->client?->email,
            $event?->client?->user?->email,
        ])->filter()->unique(fn (string $email) => strtolower($email))->values();

        return view('transactions.create', [
            'clients' => $clients,
            'events' => $events,
            'quotations' => Quotation::latest()->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'expenseConcepts' => ExpenseConcept::where('is_active', true)->orderBy('name')->get(),
            'selectedType' => $request->get('type', Transaction::TYPE_INCOME),
            'selectedEvent' => $event,
            'suggestedRecipients' => $suggestedRecipients,
            'clientRecipientMap' => $clients->mapWithKeys(fn (Client $client) => [
                $client->id => collect([$client->email, $client->user?->email])->filter()->unique()->values(),
            ]),
            'eventRecipientMap' => $events->mapWithKeys(fn (Event $event) => [
                $event->id => collect([$event->client?->email, $event->client?->user?->email])->filter()->unique()->values(),
            ]),
        ]);
    }

    public function store(
        Request $request,
        TransactionReferenceGenerator $referenceGenerator,
        ReceiptEmailSender $emailSender,
    ) {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'supplier_id' => [Rule::excludeIf($request->input('type') !== Transaction::TYPE_EXPENSE), 'nullable', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'expense_concept_id' => [Rule::excludeIf($request->input('type') !== Transaction::TYPE_EXPENSE), 'nullable', Rule::exists('expense_concepts', 'id')->where('is_active', true)],
            'type' => ['required', 'in:income,expense'],
            'scope' => ['required', 'in:event,operation'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255', Rule::unique('transactions', 'reference')],
            'status' => ['required', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string'],
            'receipt_to' => ['nullable', 'string', 'max:4000', 'required_with:receipt_cc', new EmailList],
            'receipt_cc' => ['nullable', 'string', 'max:4000', new EmailList],
        ]);

        if ($data['scope'] === 'event' && empty($data['event_id'])) {
            return back()->withErrors(['event_id' => 'Selecciona un evento para movimientos de evento.'])->withInput();
        }

        if ($data['type'] !== Transaction::TYPE_EXPENSE) {
            $data['supplier_id'] = null;
            $data['expense_concept_id'] = null;
        }

        $transaction = DB::transaction(function () use ($data, $referenceGenerator): Transaction {
            if (blank($data['reference'] ?? null)) {
                $data['reference'] = $referenceGenerator->next($data['type'], $data['transaction_date']);
            }

            $data['receipt_token'] = (string) Str::uuid();

            return Transaction::create($data);
        }, 5);
        $transaction->load(['client', 'event', 'quotation']);

        $message = 'Movimiento registrado correctamente.';
        $messageType = 'success';

        if ($transaction->type === Transaction::TYPE_INCOME && $transaction->status === 'paid') {
            $emailLog = $emailSender->send(
                $transaction,
                $request->user(),
                $data['receipt_to'] ?? null,
                $data['receipt_cc'] ?? null,
            );

            if ($emailLog === null) {
                $message .= ' No se seleccionaron destinatarios; el recibo no se envió.';
            } elseif ($emailLog->status === ReceiptEmailLog::STATUS_SENT) {
                $message .= ' Se envió el recibo por correo.';
            } else {
                $message .= ' El correo no pudo enviarse, pero el movimiento se conservó. Puedes reintentarlo desde el recibo.';
                $messageType = 'warning';
            }
        }

        if (! empty($data['event_id'])) {
            return redirect()->route('events.show', $data['event_id'])->with($messageType, $message);
        }

        return redirect()->route('transactions.index')->with($messageType, $message);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['client', 'event', 'quotation', 'supplier', 'expenseConcept', 'receiptEmailLogs.sender']);

        return view('transactions.show', $this->receiptViewData($transaction));
    }

    public function pdf(Transaction $transaction)
    {
        $transaction->load(['client', 'event', 'quotation', 'supplier', 'expenseConcept']);

        $pdf = Pdf::loadView('transactions.receipt-pdf', $this->receiptViewData($transaction))
            ->setPaper('letter');

        $filename = 'recibo-'.$transaction->id.'-'.Str::slug($transaction->client?->full_name ?? 'movimiento').'.pdf';

        return $pdf->download($filename);
    }

    public function edit(Transaction $transaction)
    {
        $transaction->load(['supplier', 'expenseConcept']);

        return view('transactions.edit', [
            'transaction' => $transaction,
            'clients' => Client::orderBy('full_name')->get(),
            'events' => Event::with('client')->orderBy('event_date')->get(),
            'quotations' => Quotation::latest()->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'expenseConcepts' => ExpenseConcept::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'supplier_id' => [Rule::excludeIf($request->input('type') !== Transaction::TYPE_EXPENSE), 'nullable', Rule::exists('suppliers', 'id')->where(function ($query) use ($transaction) {
                $query->where('is_active', true)->orWhere('id', $transaction->supplier_id);
            })],
            'expense_concept_id' => [Rule::excludeIf($request->input('type') !== Transaction::TYPE_EXPENSE), 'nullable', Rule::exists('expense_concepts', 'id')->where(function ($query) use ($transaction) {
                $query->where('is_active', true)->orWhere('id', $transaction->expense_concept_id);
            })],
            'type' => ['required', 'in:income,expense'],
            'scope' => ['required', 'in:event,operation'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['scope'] === 'event' && empty($data['event_id'])) {
            return back()->withErrors(['event_id' => 'Selecciona un evento para movimientos de evento.'])->withInput();
        }

        if ($data['type'] !== Transaction::TYPE_EXPENSE) {
            $data['supplier_id'] = null;
            $data['expense_concept_id'] = null;
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
        $publicUrl = $transaction->receipt_token
            ? route('receipts.public.show', $transaction->receipt_token)
            : null;

        return [
            'transaction' => $transaction,
            'receiptTitle' => $transaction->type === Transaction::TYPE_INCOME ? 'RECIBO DE ANTICIPO' : 'RECIBO PAGO TRABAJOS',
            'amountInWords' => SpanishMoney::toWords((float) $transaction->amount),
            'logoPath' => public_path('images/hacienda-cinco-logo.png'),
            'brandGreen' => '#243834',
            'publicUrl' => $publicUrl,
        ];
    }
}
