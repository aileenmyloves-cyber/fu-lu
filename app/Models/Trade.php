<?php

namespace App\Models;

use App\Models\Admin\Currency;
use App\Constants\PaymentGatewayConst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trade extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'user_id'          => 'integer',
        'currency_id'      => 'integer',
        'amount'           => 'decimal:16',
        'rate'             => 'decimal:16',
        'rate_currency_id' => 'integer',
        'comment'          => 'string',
        'status'           => 'integer',
    ];

    public function saleCurrency(){
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function rateCurrency(){
        return $this->belongsTo(Currency::class, 'rate_currency_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction(){
        return $this->hasOne(Transaction::class, 'trade_id', 'id')->where('type', PaymentGatewayConst::TRADE);
    }

}
