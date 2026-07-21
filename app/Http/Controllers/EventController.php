<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialBalanceCalculator;
use App\Support\MoneyNormalizer;
use App\Support\SearchTerm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search = SearchTerm::clean($request->query('search'));

        $events = Event::with('client')
            ->when($search !== '', function ($query) use ($search) {
                $like = SearchTerm::like($search);

                $query->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)
                        ->orWhere('event_type', 'like', $like)
                        ->orWhere('event_date', 'like', $like)
                        ->orWhereHas('client', fn ($query) => $query->where('full_name', 'like', $like));
                });
            })
            ->orderBy('event_date')
            ->paginate(15)
            ->withQueryString();

        return view('events.index', compact('events', 'search'));
    }

    public function create()
    {
        return view('events.create', [
            'clients' => Client::orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(MoneyNormalizer::normalizeArray($request->all(), ['budget_estimate']));

        $data = $this->validateEvent($request);
        Event::create($data);

        return redirect()->route('events.index')->with('success', 'Evento creado correctamente.');
    }

    public function show(Event $event, FinancialBalanceCalculator $balanceCalculator)
    {
        $event->load([
            'client',
            'transactions' => fn ($query) => $query->latest('transaction_date'),
            'documents',
            'tasks.assignedUser',
            'notesList.user',
            'quotations',
        ]);

        $financialBalance = $balanceCalculator->forEvent($event);
        $approvedQuotationTotal = $financialBalance['approved_quotation_total'];
        $income = $financialBalance['paid_income'];
        $expenses = $financialBalance['paid_expenses'];
        $balance = $financialBalance['cash_balance'];
        $pendingIncome = $financialBalance['pending_receivable'];

        $timeline = collect()
            ->merge($event->transactions->map(fn ($transaction) => [
                'date' => $transaction->created_at,
                'type' => $transaction->type === Transaction::TYPE_INCOME ? 'Ingreso' : 'Gasto',
                'title' => $transaction->type === Transaction::TYPE_INCOME ? 'Ingreso registrado' : 'Gasto registrado',
                'description' => '$'.number_format($transaction->amount, 2).' · '.($transaction->category ?? 'Sin categoría'),
                'color' => $transaction->type === Transaction::TYPE_INCOME ? 'green' : 'red',
            ]))
            ->merge($event->documents->map(fn ($document) => [
                'date' => $document->created_at,
                'type' => 'Documento',
                'title' => 'Documento cargado',
                'description' => $document->original_name,
                'color' => 'blue',
            ]))
            ->merge($event->notesList->map(fn ($note) => [
                'date' => $note->created_at,
                'type' => 'Nota',
                'title' => 'Nota agregada',
                'description' => $note->note,
                'color' => 'gray',
            ]))
            ->sortByDesc('date')
            ->values();

        $users = User::assignableToEventTasks()
            ->orderBy('name')
            ->get();

        return view('events.show', compact(
            'event',
            'users',
            'approvedQuotationTotal',
            'income',
            'expenses',
            'balance',
            'pendingIncome',
            'timeline',
        ));
    }

    public function edit(Event $event)
    {
        return view('events.edit', [
            'event' => $event,
            'clients' => Client::orderBy('full_name')->get(),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $request->merge(MoneyNormalizer::normalizeArray($request->all(), ['budget_estimate']));

        $event->update($this->validateEvent($request));

        return redirect()->route('events.index')->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Evento eliminado correctamente.');
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Event::STATUSES)],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'guest_count' => ['nullable', 'integer', 'min:0'],
            'budget_estimate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
