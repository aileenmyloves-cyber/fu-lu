<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class TradeLogs extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $statusInfo = [
            'ongoing'              => 1,
            'pending'              => 2,
            'cancelled'            => 4,
            'payment_pending'      => 5,
            'complete'             => 6,
            'trade_cancel_request' => 7,
            'trade_cancel_by_user' => 8,
        ];


        return[
            'id'                => $this->id,
            'trx'               => $this->trx_id,
            'transaction_type'  => __("Trade Logs"),
            'type'              => $this->attribute,
            'selling_amount'    => get_amount($this->request_amount,$this->trade->saleCurrency->code, get_wallet_precision($this->trade->saleCurrency)),
            'asking_amount'     => get_amount($this->trade->rate,$this->trade->rateCurrency->code, get_wallet_precision($this->trade->rateCurrency)),
            'exchange_rate'     => "1 = ".get_amount(($this->trade->rate / $this->trade->amount), $this->trade->rateCurrency->code,get_wallet_precision($this->trade->rateCurrency)),
            'status'            => $this->tradeStringStatus->value ,
            'date_time'         => $this->created_at,
            'statusInfo'        => $statusInfo,

        ];

    }
}
