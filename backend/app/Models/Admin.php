<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';

    protected $fillable = [
        'chef_de_projet_id',
    ];

    public function chefDeProjet()
    {
        return $this->belongsTo(ChefDeProjet::class, 'chef_de_projet_id');
    }
}