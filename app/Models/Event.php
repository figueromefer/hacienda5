<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

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
        'address',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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