<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'titre',
        'description',
        'priorite',
        'etat',
        'project_id',
        'testeur_id',
        'developpeur_id'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function testeur()
    {
        return $this->belongsTo(User::class, 'testeur_id');
    }

    public function developpeur()
    {
        return $this->belongsTo(User::class, 'developpeur_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
