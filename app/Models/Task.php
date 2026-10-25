<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'type',
        'enabled',
        'private',
        'name',
        'short_description',
        'long_description',
        'icon',
        'skippable',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'private' => 'boolean',
        'skippable' => 'boolean',
        'long_description' => 'array',
    ];

    public function responses()
    {
        return $this->hasMany(TaskResponse::class, 'task_id');
    }

    public function recipients()
    {
        return $this->hasMany(TaskRecipient::class, 'task_id');
    }

    public function questions()
    {
        return $this->hasMany(TaskQuestion::class, 'task_id');
    }
}
