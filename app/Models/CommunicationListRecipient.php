<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationListRecipient extends Model
{
    use HasFactory;

    protected $table = 'communication_list_recipients';

    protected $fillable = [
        'list_id',
        'recipient_id',
        'recipient_type',
    ];
}
