<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;
    protected $fillable = [
        'message_id',
        'category',
        'confidence_score',
        'priority',
        'assigned_team_id',
        'status',
        'gemini_reasoning',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function assignedTeam()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }
}
