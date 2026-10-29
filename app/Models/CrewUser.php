<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewUser extends Model
{
    use HasFactory;

    protected $table = 'crew_users';

    protected $fillable = [
        'user_id',
        'crew_id',
        'team_id',
        'role_id',
        'custom_title',
    ];

    /** @return BelongsTo<Crew, $this> */
    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class, 'crew_id');
    }

    /** @return BelongsTo<CrewTeam, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(CrewTeam::class, 'team_id');
    }

    /** @return BelongsTo<CrewRole, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(CrewRole::class, 'role_id');
    }
}
