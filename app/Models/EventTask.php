<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DONE,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_DONE => 'Completada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    public const STATUS_CLASSES = [
        self::STATUS_PENDING => 'bg-amber-100 text-amber-800',
        self::STATUS_DONE => 'bg-emerald-100 text-emerald-800',
        self::STATUS_CANCELLED => 'bg-gray-200 text-gray-800',
    ];

    protected $fillable = [
        'event_id',
        'title',
        'due_date',
        'status',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusClassesAttribute(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
