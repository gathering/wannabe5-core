<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCrewHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_name', 'crew_name', 'title',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'user_id', 'created_at', 'updated_at', 'event_id', 'crew_id',
    ];

    public function userProfile()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
