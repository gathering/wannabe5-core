<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationQuestion extends Model
{
    use HasFactory;

    protected $table = 'application_questions';

    protected $fillable = [
        'crew_id',
        'event_id',
        'type',
        'title',
        'description',
        'required',
        'options',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
    ];
}
