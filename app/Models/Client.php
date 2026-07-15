<?php

namespace App\Models;

use App\Support\DomainLabels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'full_name',
        'company_name',
        'email',
        'phone',
        'alternate_phone',
        'source',
        'notes',
    ];

    public function getTypeLabelAttribute(): string
    {
        return DomainLabels::clientType($this->type);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
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
}
