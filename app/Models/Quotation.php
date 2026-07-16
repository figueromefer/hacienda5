<?php

namespace App\Models;

use App\Support\DomainLabels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'event_id',
        'folio',
        'status',
        'subtotal',
        'discount',
        'total',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function getStatusLabelAttribute(): string
    {
        return DomainLabels::quotationStatus($this->status);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }
}
