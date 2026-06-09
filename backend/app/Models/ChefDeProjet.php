<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChefDeProjet extends Model
{
    protected $table = 'chefs_projet';

    protected $fillable = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'chef_de_projet_id');
    }
}