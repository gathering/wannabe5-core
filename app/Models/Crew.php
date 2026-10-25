<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function users()
    {
        return $this->hasMany(CrewUser::class, 'crew_id');
    }

    public function teams()
    {
        return $this->hasMany(CrewTeam::class, 'crew_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_crew');
    }

    public function children()
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
