<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessAttribute extends Model
{
    use HasFactory;

    protected $table = 'access_attributes';

    protected $fillable = [
        'source',
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function description()
    {
        return $this->belongsTo(AccessAttributeType::class, 'key', 'key');
    }
}
