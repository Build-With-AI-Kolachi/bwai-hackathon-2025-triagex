<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'keywords',
        'category',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];
}
