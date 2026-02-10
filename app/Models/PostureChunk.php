<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostureChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'score', 
        'slouch_duration', 
        'duration_seconds',
        'alert_count'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}