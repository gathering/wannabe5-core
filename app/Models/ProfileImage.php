<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'path',
        'approval_state',
        'decline_reason',
        'crop',
    ];

    protected $casts = [
        'crop' => 'array',
    ];
}
