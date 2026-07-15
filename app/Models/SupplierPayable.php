<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupplierPayable extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['supplier_id', 'expense_concept_id', 'event_id', 'description', 'total_amount', 'due_date', 'status', 'notes', 'created_by'];

    protected $casts = ['total_amount' => 'decimal:2', 'due_date' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function expenseConcept()
    {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function paidTransactions()
    {
        return $this->transactions()->where('type', Transaction::TYPE_EXPENSE)->where('status', 'paid');
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) ($this->paid_amount_sum ?? $this->paidTransactions()->sum('amount'));
    }

    public function getBalanceAttribute(): float
    {
        return max(0, round((float) $this->total_amount - $this->paid_amount, 2));
    }

    public function scopeWithPaidAmount(Builder $query): Builder
    {
        return $query->withSum(['transactions as paid_amount_sum' => fn ($query) => $query->where('type', Transaction::TYPE_EXPENSE)->where('status', 'paid')], 'amount');
    }

    public function refreshAutomaticStatus(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }

        $paid = $this->paidTransactions()->sum('amount');
        $status = $paid <= 0 ? self::STATUS_PENDING : ($paid >= (float) $this->total_amount ? self::STATUS_PAID : self::STATUS_PARTIALLY_PAID);
        $this->update(['status' => $status]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente', self::STATUS_PARTIALLY_PAID => 'Pago parcial',
            self::STATUS_PAID => 'Pagada', self::STATUS_CANCELLED => 'Cancelada', default => $this->status,
        };
    }
}
