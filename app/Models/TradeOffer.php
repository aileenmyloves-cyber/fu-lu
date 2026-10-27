<?php

namespace App\Models;

use App\Models\Admin\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TradeOffer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'type'             => 'string',
        'trade_id'         => 'integer',
        'trade_user_id'    => 'integer',
        'creator_id'       => 'integer',
        'receiver_id'      => 'integer',
        'amount'           => 'decimal:16',
        'rate'             => 'decimal:16',
        'sale_currency_id' => 'integer',
        'rate_currency_id' => 'integer',
        'status'           => 'integer',
    ];


    public  function trades()
    {
        return $this->belongsTo(Trade::class, 'trade_id');
    }

    public function saleCurrency(){
        return $this->belongsTo(Currency::class, 'sale_currency_id');
    }

    public function rateCurrency(){
        return $this->belongsTo(Currency::class, 'rate_currency_id');
    }

    public function receiver(){
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'creator_id');
    }


}
