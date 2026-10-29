<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationComment extends Model
{
    use HasFactory;

    protected $table = 'application_comments';

    protected $fillable = [
        'application_id',
        'user_id',
        'comment',
    ];
}
