<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarConnection extends Model
{
    protected $fillable = [
        'user_id', 'google_email', 'access_token', 'refresh_token', 'token_expires_at',
        'calendar_id', 'calendar_name',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
