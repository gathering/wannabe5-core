<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationMessage extends Model
{
    use HasFactory;

    protected $table = 'communication_messages';

    protected $fillable = [
        'sent',
        'state',
        'last_error',
        'tries',
        'channel',
        'notification_name',
        'recipient',
        'data',
        'source',
    ];

    protected $casts = [
        'sent' => 'datetime',
        'recipient' => 'array',
        'data' => 'array',
        'source' => 'array',
    ];
}
