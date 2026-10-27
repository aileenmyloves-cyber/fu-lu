<?php

namespace App\Http\Resources\User;

use App\Constants\PaymentGatewayConst;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceLogs extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $attribute = $this->attribute == PaymentGatewayConst::SEND ? __('Buy') : __('Sale');

        if (auth()->user()->id == $this->user_id && $this->attribute == PaymentGatewayConst::SEND) {
            $amount = get_amount($this->trade->rate, $this->trade->rateCurrency->code, get_wallet_precision($this->trade->rateCurrency));
            $payable = get_amount($this->trade->rate+$this->charge->total_charge, $this->trade->rateCurrency->code, get_wallet_precision($this->trade->rateCurrency));

            return[
                'id'               => $this->id,
                'trx'              => $this->trx_id,
                'type'             => $this->attribute,
                'transaction_type' => __("Marketplace Logs"),
                'method'           => $this->currency->name,
                'attribute'        => $attribute,
                'selling_amount'   => $amount,
                'payable_amount'   => $payable,
                'asking_amount'    => get_amount($this->trade->rate,$this->trade->rateCurrency->code, get_wallet_precision($this->trade->rateCurrency)),
                'seller'           => $this->trade->user->fullname,
                'exchange_rate'    => "1 = ".get_amount(($this->trade->rate / $this->trade->amount), $this->trade->rateCurrency->code,get_wallet_precision($this->trade->rateCurrency)),
                'status'           => $this->tradeStringStatus->value,
                'date_time'        => $this->created_at,

            ];
        } else {
            $amount = get_amount($this->trade->amount, $this->trade->saleCurrency->code, get_wallet_precision($this->trade->saleCurrency));
            $payable = get_amount($this->request_amount, $this->trade->rateCurrency->code, get_wallet_precision($this->trade->rateCurrency));

            return[
                'id'               => $this->id,
                'trx'              => $this->trx_id,
                'type'             => $this->attribute,
                'transaction_type' => __("Marketplace Logs"),
                'method'           => $this->currency->name,
                'attribute'        => $attribute,
                'selling_amount'   => get_amount($this->trade->amount,$this->trade->saleCurrency->code, get_wallet_precision($this->trade->saleCurrency)),
                'asking_amount'    => get_amount($this->trade->rate,$this->trade->rateCurrency->code, get_wallet_precision($this->trade->rateCurrency)),
                'seller'           => $this->trade->user->fullname,
                'exchange_rate'    => "1 = ".get_amount(($this->trade->rate / $this->trade->amount), $this->trade->rateCurrency->code,get_wallet_precision($this->trade->rateCurrency)),
                'status'           => $this->tradeStringStatus->value,
                'date_time'        => $this->created_at,

            ];
        }




    }
}
