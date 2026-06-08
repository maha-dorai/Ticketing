<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    protected $table = 'time_logs';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'heures',
        'created_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
