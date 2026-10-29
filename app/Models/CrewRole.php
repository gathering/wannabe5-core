<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewRole extends Model
{
    use HasFactory;

    protected $table = 'crew_roles';

    protected $fillable = [
        'event_id',
        'name',
        'title',
        'description',
    ];
}
