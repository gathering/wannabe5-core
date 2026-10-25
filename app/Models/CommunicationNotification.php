<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationNotification extends Model
{
    use HasFactory;

    protected $table = 'communication_notifications';

    protected $fillable = [
        'name',
        'mail_template_id',
        'sms_template_id',
        'slack_template_id',
    ];
}
