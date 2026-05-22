<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'date_debut',
        'date_fin',
        'statut',      // 'ouvert' | 'en_cours' | 'archive'
        'created_by',  // FK vers users.id
    ];
 
    // Membres affectés au projet (Many-to-Many)
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
 
    // Admin ou chef_de_projet qui a créé le projet
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}