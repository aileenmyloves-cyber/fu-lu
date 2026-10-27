<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SudoWebhookData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'card_id'    => 'string',
        'event_id'   => 'string',
        'event_data' => 'object',
    ];
}
