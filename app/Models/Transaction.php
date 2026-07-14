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
