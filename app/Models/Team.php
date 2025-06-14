<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email_alias',
        'slack_channel',
    ];

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function classifications()
    {
        return $this->hasMany(Classification::class, 'assigned_team_id');
    }
}
