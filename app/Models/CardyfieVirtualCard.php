<?php

namespace App\Models;

use App\Constants\GlobalConst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardyfieVirtualCard extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

     protected $appends = ['cardStatus'];

    protected $casts = [
        'id'            => 'integer',
        'user_type'     => 'string',
        'user_id'       => 'integer',
        'ulid'          => 'string',
        'customer_ulid' => 'string',
        'card_name'     => 'string',
        'amount'        => 'decimal:8',
        'currency'      => 'string',
        'card_type'     => 'string',
        'card_provider' => 'string',
        'card_exp_time' => 'string',
        'masked_pan'    => 'string',
        'address'       => 'string',
        'status'        => 'string',
        'env'           => 'string',
        'is_default'       => 'boolean',
    ];

     public function getCardStatusAttribute() {
        $status = $this->status;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == GlobalConst::CARD_ENABLED_STATUS) {
            $data = [
                'class'     => "text--success",
                'value'     => "ENABLED",
            ];
        }else if($status == GlobalConst::CARD_PROCESSING_STATUS) {
            $data = [
                'class'     => "text--warning",
                'value'     => "PROCESSING",
            ];
        }else if($status == GlobalConst::CARD_FREEZE_STATUS) {
            $data = [
                'class'     => "text--warning",
                'value'     => "FREEZE",
            ];
        }else {
            $data = [
                'class'     => "text--danger",
                'value'     => "CLOSED",
            ];
        }
        return (object) $data;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
