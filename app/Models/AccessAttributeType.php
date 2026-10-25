<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessAttributeType extends Model
{
    use HasFactory;

    protected $table = 'access_attribute_types';

    protected $fillable = [
        'source',
        'key',
        'summary',
        'description',
    ];

    public function attributes()
    {
        return $this->hasMany(AccessAttribute::class, 'key', 'key');
    }
}
