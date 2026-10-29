<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'optional',
        'hidden',
        'name',
        'description',
        'type',
        'options',
    ];

    protected $casts = [
        'optional' => 'boolean',
        'hidden' => 'boolean',
        'options' => 'array',
    ];
}
