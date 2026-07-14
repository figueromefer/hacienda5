<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSequence extends Model
{
    protected $fillable = [
        'year',
        'type',
        'last_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];
}
