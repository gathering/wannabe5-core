<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'event_id',
        'title',
        'slug',
        'content',
        'published_at',
        'author_id',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class);
    }

    public function latestVersion()
    {
        return $this->versions()->latest('created_at')->first();
    }
}
