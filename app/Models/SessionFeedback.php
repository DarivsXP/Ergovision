<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionFeedback extends Model
{
    protected $fillable = [
        'user_id',
        'fatigue_level',
        'accuracy_rating',
        'comments',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}