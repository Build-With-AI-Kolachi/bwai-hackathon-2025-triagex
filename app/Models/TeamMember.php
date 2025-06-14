<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'team_id',
        'is_on_call',
    ];

    public function user()
    {
        return $this->belongsTo(User::class); // Assuming default User model
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
