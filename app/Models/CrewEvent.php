<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewEvent extends Model
{
    use HasFactory;

    protected $table = 'crew_events';

    protected $fillable = [
        'name',
        'slug',
        'location',
        'start_date',
        'end_date',
        'visible',
        'active',
        'applications_open',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'visible' => 'boolean',
        'active' => 'boolean',
        'applications_open' => 'boolean',
    ];

    public function crews()
    {
        return $this->hasMany(Crew::class, 'event_id');
    }
}
