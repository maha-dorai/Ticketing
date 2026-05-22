<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'priorite',
        'etat',
        'project_id',
        'testeur_id',
        'developpeur_id',
        'proposed_developpeur_id',
        'assignment_status',
        'force_assigned',
        'rejected_by',
    ];

    protected $casts = [
        'force_assigned' => 'boolean',
        'rejected_by'    => 'array',
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

    public function proposedDeveloppeur()
    {
        return $this->belongsTo(User::class, 'proposed_developpeur_id');
    }

    public function isAssignmentApproved(): bool
    {
        return $this->assignment_status === 'approved' && $this->developpeur_id !== null;
    }

    public function isAssignmentPending(): bool
    {
        return $this->assignment_status === 'pending' && $this->proposed_developpeur_id !== null;
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
