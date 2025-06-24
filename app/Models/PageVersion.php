<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    protected $fillable = [
        'page_id',
        'title',
        'slug',
        'content',
        'version_number',
        'edited_by',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
