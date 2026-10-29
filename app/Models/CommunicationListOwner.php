<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationListOwner extends Model
{
    use HasFactory;

    protected $table = 'communication_list_owners';

    protected $fillable = [
        'list_id',
        'owner_id',
        'owner_type',
    ];
}
