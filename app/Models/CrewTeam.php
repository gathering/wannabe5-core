<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'crew_id',
        'name',
    ];
}
