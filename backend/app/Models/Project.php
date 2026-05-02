<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['nom', 'description', 'date_debut', 'date_fin', 'statut'];

    // Relation Many-to-Many avec les Utilisateurs
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
