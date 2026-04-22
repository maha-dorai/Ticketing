<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    protected $fillable = [
        'nom', 'prenom', 'email', 'mot_de_passe',
        'role', 'statut', 'github_link',
        'reset_token', 'reset_token_expires',
    ];

    protected $hidden = ['mot_de_passe', 'reset_token'];

    // JWT مطلوب
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => $this->role, 'email' => $this->email];
    }

    // لأن عمود الباسورد اسمه mot_de_passe مش password
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }
}