<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationList extends Model
{
    use HasFactory;

    protected $table = 'communication_lists';

    protected $fillable = [
        'identifier',
        'type',
        'summary',
        'event_id',
    ];

    public function owners()
    {
        return $this->hasMany(CommunicationListOwner::class, 'list_id');
    }

    public function recipients()
    {
        return $this->hasMany(CommunicationListRecipient::class, 'list_id');
    }
}
