<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    use HasFactory;

    protected $table = 'communication_templates';

    protected $fillable = [
        'name',
        'title',
        'content',
        'schema',
        'source_type',
        'view_id',
    ];

    protected $casts = [
        'schema' => 'array',
    ];
}
