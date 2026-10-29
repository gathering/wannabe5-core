<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crew extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_crew',
        'event_id',
        'name',
        'hidden',
        'can_apply',
        'short_description',
        'long_description',
        'sorted_weight',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'can_apply' => 'boolean',
    ];

    /** @return HasMany<CrewUser, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(CrewUser::class, 'crew_id');
    }

    /** @return HasMany<CrewTeam, $this> */
    public function teams(): HasMany
    {
        return $this->hasMany(CrewTeam::class, 'crew_id');
    }

    /** @return HasMany<CrewRole, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(CrewRole::class, 'event_id', 'event_id');
    }

    /** @return BelongsTo<Crew, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_crew');
    }

    /** @return HasMany<Crew, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_crew')->with('children');
    }

    /**
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = (int) $child->id;
            $ids = array_merge($ids, $child->descendantIds());
        }

        return array_values(array_unique($ids));
    }
}
