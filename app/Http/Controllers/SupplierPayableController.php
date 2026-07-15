<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ExpenseConcept;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Services\SupplierPayablePaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierPayableController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierPayable::query()->with(['supplier', 'expenseConcept', 'event'])->withPaidAmount()->latest();
        $this->applyFilters($query, $request);
        $summary = (clone $query)->get();

        return view('supplier-payables.index', [
            'payables' => $query->paginate(15)->withQueryString(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'expenseConcepts' => ExpenseConcept::orderBy('name')->get(),
            'totalCommitted' => $summary->sum(fn ($item) => (float) $item->total_amount),
            'totalPaid' => $summary->sum->paid_amount,
            'totalBalance' => $summary->sum->balance,
            'overdueCount' => $summary->filter(fn ($item) => $item->due_date?->isPast() && $item->balance > 0 && $item->status !== SupplierPayable::STATUS_CANCELLED)->count(),
        ]);
    }

    public function create()
    {
        return view('supplier-payables.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatePayable($request);
        $data['created_by'] = $request->user()->id;
        $payable = SupplierPayable::create($data);

        return redirect()->route('supplier-payables.show', $payable)->with('success', 'Cuenta por pagar registrada.');
    }

    public function show(SupplierPayable $supplierPayable)
    {
        $supplierPayable->load(['supplier', 'expenseConcept', 'event', 'creator'])->loadSum(['transactions as paid_amount_sum' => fn ($q) => $q->where('type', 'expense')->where('status', 'paid')], 'amount');
        $payments = $supplierPayable->transactions()->where('type', 'expense')->latest('transaction_date')->get();

        return view('supplier-payables.show', compact('supplierPayable', 'payments'));
    }

    public function edit(SupplierPayable $supplierPayable)
    {
        abort_if($supplierPayable->status === SupplierPayable::STATUS_CANCELLED, 422);

        return view('supplier-payables.edit', $this->formData($supplierPayable) + compact('supplierPayable'));
    }

    public function update(Request $request, SupplierPayable $supplierPayable)
    {
        abort_if($supplierPayable->status === SupplierPayable::STATUS_CANCELLED, 422);
        $data = $this->validatePayable($request, $supplierPayable);
        if ((float) $data['total_amount'] < $supplierPayable->paid_amount) {
            throw ValidationException::withMessages(['total_amount' => 'El monto total no puede ser menor que lo ya pagado.']);
        }
        $supplierPayable->update($data);
        $supplierPayable->refreshAutomaticStatus();

        return redirect()->route('supplier-payables.show', $supplierPayable)->with('success', 'Cuenta actualizada.');
    }

    public function cancel(Request $request, SupplierPayable $supplierPayable)
    {
        $request->validate(['confirm_cancel' => ['accepted']]);
        if ($supplierPayable->status === SupplierPayable::STATUS_PAID) {
            $request->validate(['confirm_paid_cancel' => ['accepted']], ['confirm_paid_cancel.accepted' => 'Confirma expresamente la cancelación de la cuenta liquidada.']);
        }
        $supplierPayable->update(['status' => SupplierPayable::STATUS_CANCELLED]);

        return redirect()->route('supplier-payables.show', $supplierPayable)->with('success', 'Cuenta cancelada; sus pagos históricos se conservaron.');
    }

    public function paymentForm(SupplierPayable $supplierPayable)
    {
        $supplierPayable->loadSum(['transactions as paid_amount_sum' => fn ($q) => $q->where('type', 'expense')->where('status', 'paid')], 'amount');
        abort_if(in_array($supplierPayable->status, [SupplierPayable::STATUS_CANCELLED, SupplierPayable::STATUS_PAID], true), 422);

        return view('supplier-payables.payment', compact('supplierPayable'));
    }

    public function pay(Request $request, SupplierPayable $supplierPayable, SupplierPayablePaymentService $service)
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->register($supplierPayable, $data);

        return redirect()->route('supplier-payables.show', $supplierPayable)->with('success', 'Pago registrado correctamente.');
    }

    private function validatePayable(Request $request, ?SupplierPayable $payable = null): array
    {
        return $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('is_active', true)->when($payable, fn ($q) => $q->orWhere('id', $payable->supplier_id)))],
            'expense_concept_id' => ['nullable', Rule::exists('expense_concepts', 'id')->where(fn ($q) => $q->where('is_active', true)->when($payable, fn ($q) => $q->orWhere('id', $payable->expense_concept_id)))],
            'event_id' => ['nullable', Rule::exists('events', 'id')->where(fn ($q) => $q->where('status', '!=', Event::STATUS_CANCELLED)->when($payable, fn ($q) => $q->orWhere('id', $payable->event_id)))],
            'description' => ['required', 'string', 'max:255'], 'total_amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string'],
        ]);
    }

    private function formData(?SupplierPayable $payable = null): array
    {
        return [
            'suppliers' => Supplier::where('is_active', true)->when($payable, fn ($q) => $q->orWhere('id', $payable->supplier_id))->orderBy('name')->get(),
            'expenseConcepts' => ExpenseConcept::where('is_active', true)->when($payable?->expense_concept_id, fn ($q) => $q->orWhere('id', $payable->expense_concept_id))->orderBy('name')->get(),
            'events' => Event::where('status', '!=', Event::STATUS_CANCELLED)->when($payable?->event_id, fn ($q) => $q->orWhere('id', $payable->event_id))->orderByDesc('event_date')->get(),
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        $query->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', '%'.$request->string('q')->trim().'%')->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('expense_concept_id'), fn ($q) => $q->where('expense_concept_id', $request->integer('expense_concept_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereDate('due_date', '<', today())->whereNotIn('status', [SupplierPayable::STATUS_PAID, SupplierPayable::STATUS_CANCELLED]))
            ->when($request->filled('due_from'), fn ($q) => $q->whereDate('due_date', '>=', $request->date('due_from')))
            ->when($request->filled('due_to'), fn ($q) => $q->whereDate('due_date', '<=', $request->date('due_to')));
    }
}
