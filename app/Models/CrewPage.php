<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrewPage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'crew_pages';

    protected $fillable = [
        'type',
        'event_id',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}
