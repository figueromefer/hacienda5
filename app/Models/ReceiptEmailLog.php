<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptEmailLog extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'transaction_id',
        'sent_by',
        'to_recipients',
        'cc_recipients',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'sent_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
