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
use App\Support\DomainLabels;
use App\Support\MoneyNormalizer;
use App\Support\SearchTerm;
use App\Support\SpanishMoney;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['client', 'event', 'quotation', 'supplier', 'expenseConcept'])
            ->latest('transaction_date');

        $search = SearchTerm::clean($request->query('search'));

        if ($search !== '') {
            $like = SearchTerm::like($search);
            $normalizedSearch = Str::lower($search);
            $types = collect(DomainLabels::TRANSACTION_TYPES)
                ->filter(fn (string $label) => str_contains(Str::lower($label), $normalizedSearch))
                ->keys();
            $statuses = collect(DomainLabels::TRANSACTION_STATUSES)
                ->filter(fn (string $label) => str_contains(Str::lower($label), $normalizedSearch))
                ->keys();
            $methods = collect(DomainLabels::TRANSACTION_METHODS)
                ->filter(fn (string $label) => str_contains(Str::lower($label), $normalizedSearch))
                ->keys();

            $query->where(function ($query) use ($like, $types, $statuses, $methods) {
                $query->where('reference', 'like', $like)
                    ->orWhere('type', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('method', 'like', $like)
                    ->orWhere('amount', 'like', $like)
                    ->orWhere('transaction_date', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->when($types->isNotEmpty(), fn ($query) => $query->orWhereIn('type', $types))
                    ->when($statuses->isNotEmpty(), fn ($query) => $query->orWhereIn('status', $statuses))
                    ->when($methods->isNotEmpty(), fn ($query) => $query->orWhereIn('method', $methods))
                    ->orWhereHas('client', fn ($query) => $query->where('full_name', 'like', $like))
                    ->orWhereHas('event', fn ($query) => $query->where('title', 'like', $like))
                    ->orWhereHas('quotation', fn ($query) => $query->where('folio', 'like', $like))
                    ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', $like))
                    ->orWhereHas('expenseConcept', fn ($query) => $query->where('name', 'like', $like));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
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
            'search' => $search,
        ]);
    }

    public function expenses(Request $request)
    {
        return $this->typeIndex($request, Transaction::TYPE_EXPENSE);
    }

    public function incomes(Request $request)
    {
        return $this->typeIndex($request, Transaction::TYPE_INCOME);
    }

    private function typeIndex(Request $request, string $type)
    {
        $query = Transaction::with(['client', 'event', 'supplier', 'expenseConcept'])
            ->where('type', $type)
            ->latest('transaction_date')
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();

            $query->where(function ($query) use ($search) {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($query) => $query->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('event', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('expenseConcept', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->date('to'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('expense_concept_id')) {
            $query->where('expense_concept_id', $request->integer('expense_concept_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $totalsQuery = clone $query;

        return view('transactions.type-index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'expenseConcepts' => ExpenseConcept::orderBy('name')->get(),
            'type' => $type,
            'total' => (clone $totalsQuery)->where('status', Transaction::STATUS_PAID)->sum('amount'),
            'pendingTotal' => (clone $totalsQuery)->where('status', Transaction::STATUS_PENDING)->sum('amount'),
        ]);
    }

    public function create(Request $request)
    {
        $selectedType = in_array($request->string('type')->toString(), [Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE], true)
            ? $request->string('type')->toString()
            : Transaction::TYPE_INCOME;
        $event = $request->filled('event_id') ? Event::with('client.user')->find($request->event_id) : null;
        $clients = Client::with('user')->orderBy('full_name')->get();
        $events = Event::with('client.user')->orderBy('event_date')->get();
        $suggestedRecipients = collect([
            $event?->client?->email,
            $event?->client?->user?->email,
        ])->filter()->unique(fn (string $email) => strtolower($email))->values();

        $origin = match ($request->string('origin')->toString()) {
            'expenses' => $selectedType === Transaction::TYPE_EXPENSE ? 'expenses' : 'transactions',
            'incomes' => $selectedType === Transaction::TYPE_INCOME ? 'incomes' : 'transactions',
            default => 'transactions',
        };
        $cancelUrl = $event
            ? route('events.show', $event)
            : match ($origin) {
                'expenses' => route('expenses.index'),
                'incomes' => route('incomes.index'),
                default => route('transactions.index'),
            };

        return view('transactions.create', [
            'clients' => $clients,
            'events' => $events,
            'quotations' => Quotation::latest()->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'expenseConcepts' => ExpenseConcept::where('is_active', true)->orderBy('name')->get(),
            'selectedType' => $selectedType,
            'selectedEvent' => $event,
            'fixedEventContext' => (bool) $event,
            'cancelUrl' => $cancelUrl,
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
        $request->merge(['amount' => MoneyNormalizer::normalize($request->input('amount'))]);

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
            'reference' => ['nullable', 'string', 'max:255', Rule::unique('transactions', 'reference')],
            'notes' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'receipt_to' => ['nullable', 'string', 'max:4000', 'required_with:receipt_cc', new EmailList],
            'receipt_cc' => ['nullable', 'string', 'max:4000', new EmailList],
        ]);

        if ($data['scope'] === 'event' && empty($data['event_id'])) {
            return back()->withErrors(['event_id' => 'Selecciona un evento para movimientos de evento.'])->withInput();
        }

        $this->validateAssociations($data);

        if ($data['type'] !== Transaction::TYPE_EXPENSE) {
            $data['supplier_id'] = null;
            $data['expense_concept_id'] = null;
        }

        $data['status'] = Transaction::STATUS_PAID;
        $data['category'] = null;
        unset($data['proof_file']);

        $newProofPath = $this->storeProof($request, $data);

        try {
            $transaction = DB::transaction(function () use ($data, $referenceGenerator): Transaction {
                if (blank($data['reference'] ?? null)) {
                    $data['reference'] = $referenceGenerator->next($data['type'], $data['transaction_date']);
                }

                $data['receipt_token'] = (string) Str::uuid();

                return Transaction::create($data);
            }, 5);
        } catch (Throwable $exception) {
            if ($newProofPath) {
                Storage::disk('local')->delete($newProofPath);
            }

            throw $exception;
        }
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

    public function show(Request $request, Transaction $transaction)
    {
        $transaction->load(['client', 'event', 'quotation', 'supplier', 'expenseConcept', 'receiptEmailLogs.sender']);

        return view('transactions.show', [
            ...$this->receiptViewData($transaction),
            ...$this->receiptBackContext($transaction, $request->string('origin')->toString()),
        ]);
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
        abort_if($transaction->status === Transaction::STATUS_CANCELLED, 422, 'Un movimiento cancelado no se puede editar.');
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
        abort_if($transaction->status === Transaction::STATUS_CANCELLED, 422, 'Un movimiento cancelado no se puede editar.');
        $request->merge(['amount' => MoneyNormalizer::normalize($request->input('amount'))]);

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
            'notes' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($data['scope'] === 'event' && empty($data['event_id'])) {
            return back()->withErrors(['event_id' => 'Selecciona un evento para movimientos de evento.'])->withInput();
        }

        $this->validateAssociations($data);

        if ($data['type'] !== Transaction::TYPE_EXPENSE) {
            $data['supplier_id'] = null;
            $data['expense_concept_id'] = null;
        }

        unset($data['proof_file']);
        $previousProofPath = $transaction->proof_file_path;
        $newProofPath = $this->storeProof($request, $data);

        try {
            DB::transaction(fn () => $transaction->update($data));
        } catch (Throwable $exception) {
            if ($newProofPath) {
                Storage::disk('local')->delete($newProofPath);
            }

            throw $exception;
        }

        if ($newProofPath && $previousProofPath) {
            Storage::disk('local')->delete($previousProofPath);
        }

        return redirect()->route('transactions.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        $cancelled = DB::transaction(function () use ($request, $transaction): bool {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($locked->status === Transaction::STATUS_CANCELLED) {
                return false;
            }

            $locked->update([
                'status' => Transaction::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
            ]);

            return true;
        });

        if (! $cancelled) {
            return back()->with('warning', 'El movimiento ya estaba cancelado; no se modificó su auditoría.');
        }

        return back()->with('success', 'Movimiento cancelado correctamente.');
    }

    public function downloadProof(Transaction $transaction)
    {
        abort_unless($transaction->proof_file_path && Storage::disk('local')->exists($transaction->proof_file_path), 404);

        return Storage::disk('local')->download(
            $transaction->proof_file_path,
            $transaction->proof_original_name ?: 'comprobante',
            ['Content-Type' => $transaction->proof_mime_type ?: 'application/octet-stream'],
        );
    }

    private function validateAssociations(array $data): void
    {
        $clientId = $data['client_id'];
        $eventId = $data['event_id'] ?? null;
        $quotationId = $data['quotation_id'] ?? null;
        $errors = [];

        if ($eventId && ! Event::query()->whereKey($eventId)->where('client_id', $clientId)->exists()) {
            $errors['event_id'] = 'El evento seleccionado no pertenece al cliente indicado.';
        }

        if ($quotationId) {
            $quotationIsCoherent = Quotation::query()
                ->whereKey($quotationId)
                ->where('client_id', $clientId)
                ->when($eventId, fn ($query) => $query->where('event_id', $eventId), fn ($query) => $query->whereNull('event_id'))
                ->exists();

            if (! $quotationIsCoherent) {
                $errors['quotation_id'] = 'La cotización no corresponde al cliente y evento seleccionados.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function storeProof(Request $request, array &$data): ?string
    {
        $proof = $request->file('proof_file');

        if (! $proof) {
            return null;
        }

        $path = $proof->store('transaction-proofs', 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'proof_file' => 'No fue posible guardar el comprobante. Intenta nuevamente.',
            ]);
        }

        $data['proof_file_path'] = $path;
        $data['proof_original_name'] = Str::limit($proof->getClientOriginalName(), 255, '');
        $data['proof_mime_type'] = $proof->getMimeType();
        $data['proof_file_size'] = $proof->getSize();

        return $path;
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

    private function receiptBackContext(Transaction $transaction, string $origin): array
    {
        return match ($origin) {
            'event' => $transaction->event_id
                ? ['backUrl' => route('events.show', $transaction->event_id), 'backLabel' => 'Volver al evento']
                : ['backUrl' => route('transactions.index'), 'backLabel' => 'Volver a Movimientos'],
            'expenses' => $transaction->type === Transaction::TYPE_EXPENSE
                ? ['backUrl' => route('expenses.index'), 'backLabel' => 'Volver a Gastos']
                : ['backUrl' => route('transactions.index'), 'backLabel' => 'Volver a Movimientos'],
            'incomes' => $transaction->type === Transaction::TYPE_INCOME
                ? ['backUrl' => route('incomes.index'), 'backLabel' => 'Volver a Ingresos']
                : ['backUrl' => route('transactions.index'), 'backLabel' => 'Volver a Movimientos'],
            default => ['backUrl' => route('transactions.index'), 'backLabel' => 'Volver a Movimientos'],
        };
    }
}
