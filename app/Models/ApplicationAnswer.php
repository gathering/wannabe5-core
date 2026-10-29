<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationAnswer extends Model
{
    use HasFactory;

    protected $table = 'application_answers';

    protected $fillable = [
        'application_id',
        'question_id',
        'user_id',
        'event_id',
        'content',
    ];

    public function question()
    {
        return $this->belongsTo(ApplicationQuestion::class, 'question_id');
    }
}
