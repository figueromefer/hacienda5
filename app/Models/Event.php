<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_TENTATIVE = 'tentative';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_RESERVED,
        self::STATUS_TENTATIVE,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'client_id',
        'title',
        'event_type',
        'status',
        'event_date',
        'start_time',
        'end_time',
        'guest_count',
        'budget_estimate',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RESERVED => 'Apartado',
            self::STATUS_TENTATIVE => 'Por confirmar',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    public function calendarColors(): array
    {
        return match ($this->status) {
            self::STATUS_RESERVED => ['background' => '#b45309', 'border' => '#92400e', 'text' => '#ffffff'],
            self::STATUS_TENTATIVE => ['background' => '#2563eb', 'border' => '#1d4ed8', 'text' => '#ffffff'],
            self::STATUS_CONFIRMED => ['background' => '#15803d', 'border' => '#166534', 'text' => '#ffffff'],
            self::STATUS_COMPLETED => ['background' => '#0f766e', 'border' => '#115e59', 'text' => '#ffffff'],
            self::STATUS_CANCELLED => ['background' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'],
            default => ['background' => '#505050', 'border' => '#374151', 'text' => '#ffffff'],
        };
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function tasks()
    {
        return $this->hasMany(EventTask::class);
    }

    public function notesList()
    {
        return $this->hasMany(EventNote::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }
}
