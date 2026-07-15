<?php

namespace App\Services;

use App\Models\SupplierPayable;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplierPayablePaymentService
{
    public function __construct(private TransactionReferenceGenerator $references) {}

    public function register(SupplierPayable $payable, array $data): Transaction
    {
        return DB::transaction(function () use ($payable, $data) {
            $payable = SupplierPayable::query()->lockForUpdate()->findOrFail($payable->id);
            $payable->loadSum(['transactions as paid_amount_sum' => fn ($q) => $q->where('type', Transaction::TYPE_EXPENSE)->where('status', 'paid')], 'amount');

            if ($payable->status === SupplierPayable::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['amount' => 'No se pueden registrar pagos en una cuenta cancelada.']);
            }
            if ($payable->balance <= 0 || $payable->status === SupplierPayable::STATUS_PAID) {
                throw ValidationException::withMessages(['amount' => 'La cuenta ya está liquidada.']);
            }
            if ((float) $data['amount'] > $payable->balance) {
                throw ValidationException::withMessages(['amount' => 'El pago no puede superar el saldo pendiente.']);
            }

            $transaction = Transaction::create([
                'client_id' => $payable->event?->client_id,
                'event_id' => $payable->event_id,
                'supplier_id' => $payable->supplier_id,
                'expense_concept_id' => $payable->expense_concept_id,
                'supplier_payable_id' => $payable->id,
                'type' => Transaction::TYPE_EXPENSE,
                'scope' => $payable->event_id ? 'event' : 'operation',
                'transaction_date' => $data['transaction_date'],
                'amount' => $data['amount'],
                'method' => $data['method'] ?? null,
                'category' => $payable->expenseConcept?->name,
                'reference' => $this->references->next(Transaction::TYPE_EXPENSE, $data['transaction_date']),
                'receipt_token' => (string) Str::uuid(),
                'status' => 'paid',
                'notes' => $data['description'] ?? null,
            ]);

            $payable->refreshAutomaticStatus();

            return $transaction;
        }, 5);
    }
}
