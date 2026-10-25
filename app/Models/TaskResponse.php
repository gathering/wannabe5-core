<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'value',
        'approved',
        'completed',
    ];

    protected $casts = [
        'value' => 'array',
        'approved' => 'boolean',
        'completed' => 'boolean',
    ];
}
