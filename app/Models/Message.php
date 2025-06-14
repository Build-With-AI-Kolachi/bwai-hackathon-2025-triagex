<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_message_id',
        'from_number',
        'message_body',
        'message_type',
        'status',
        'raw_webhook_data',
    ];

    protected $casts = [
        'raw_webhook_data' => 'array',
    ];

    public function classification()
    {
        return $this->hasOne(Classification::class);
    }
}
