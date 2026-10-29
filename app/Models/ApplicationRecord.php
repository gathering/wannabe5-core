<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationRecord extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'user_id',
        'event_id',
        'crew_id',
        'status',
        'message',
        'reviewable_at',
    ];

    protected $casts = [
        'reviewable_at' => 'datetime',
    ];

    public function answers()
    {
        return $this->hasMany(ApplicationAnswer::class, 'application_id');
    }

    public function comments()
    {
        return $this->hasMany(ApplicationComment::class, 'application_id');
    }
}
