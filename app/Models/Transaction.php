<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Transaction extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'client_id',
        'event_id',
        'quotation_id',
        'supplier_id',
        'expense_concept_id',
        'supplier_payable_id',
        'type',
        'scope',
        'transaction_date',
        'amount',
        'method',
        'category',
        'reference',
        'receipt_token',
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (Transaction $transaction): void {
            if ($transaction->isDirty('reference')) {
                throw new LogicException('La referencia de un movimiento no se puede modificar.');
            }
        });

        static::saved(function (Transaction $transaction): void {
            collect([$transaction->supplier_payable_id, $transaction->getOriginal('supplier_payable_id')])
                ->filter()->unique()->each(fn ($id) => SupplierPayable::find($id)?->refreshAutomaticStatus());
        });

        static::deleted(function (Transaction $transaction): void {
            if ($transaction->supplier_payable_id) {
                SupplierPayable::find($transaction->supplier_payable_id)?->refreshAutomaticStatus();
            }
        });
    }

    public static function referencePrefix(string $type): string
    {
        return match ($type) {
            self::TYPE_INCOME => 'ING',
            self::TYPE_EXPENSE => 'GAS',
            default => throw new LogicException('Tipo de movimiento no válido para generar una referencia.'),
        };
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function expenseConcept()
    {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function supplierPayable()
    {
        return $this->belongsTo(SupplierPayable::class);
    }

    public function receiptEmailLogs()
    {
        return $this->hasMany(ReceiptEmailLog::class)->latest();
    }

    public function getSignedAmountAttribute(): float
    {
        return $this->type === self::TYPE_EXPENSE
            ? -1 * (float) $this->amount
            : (float) $this->amount;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INCOME => 'Ingreso',
            self::TYPE_EXPENSE => 'Gasto',
            default => $this->type,
        };
    }

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            'event' => 'Evento',
            'operation' => 'Operación',
            default => $this->scope ?? '-',
        };
    }
}
