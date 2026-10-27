<?php

namespace App\Models;

use App\Models\Admin\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StrowalletVirtualCard extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const STRING_STATUS_ACTIVE     = 'active';
    const STRING_STATUS_SUCCESS    = 'success';
    const STRING_STATUS_COMPLETED  = 'completed';
    const STRING_STATUS_PENDING    = 'pending';
    const STRING_STATUS_PROCESSING = 'processing';
    const STRING_STATUS_FAILED     = 'failed';

    protected $casts            = [
        'id'                 => 'integer',
        'user_id'            => 'integer',
        'name_on_card'       => 'string',
        'card_id'            => 'string',
        'card_created_date'  => 'string',
        'card_type'          => 'string',
        'card_brand'         => 'string',
        'card_user_id'       => 'string',
        'reference'          => 'string',
        'card_status'        => 'string',
        'customer_id'        => 'string',
        'card_name'          => 'string',
        'card_number'        => 'string',
        'last4'              => 'string',
        'cvv'                => 'string',
        'expiry'             => 'string',
        'customer_email'     => 'string',
        'balance'            => 'decimal:8',
        'status'             => 'boolean',
        'is_active'          => 'boolean',
        'is_default'         => 'boolean',
        'authorization_code' => 'string',
        'auth_code_send_at'  => 'datetime',
        'mode'               => 'string',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function card_currency() {
        return $this->belongsTo(Currency::class,'currency','code');
    }
}