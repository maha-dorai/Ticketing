<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membre extends Model
{
    protected $table = 'membres';

    protected $fillable = [
        'user_id',
        'github_link',
        'role',
        'statut',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}