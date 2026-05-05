<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

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
