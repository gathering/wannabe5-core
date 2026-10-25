<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'mandatory',
        'approval_needed',
        'recipient_type',
        'recipient_id',
        'filter',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
        'approval_needed' => 'boolean',
        'filter' => 'array',
    ];
}
