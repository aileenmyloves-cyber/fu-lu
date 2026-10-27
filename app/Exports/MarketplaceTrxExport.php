<?php

namespace App\Exports;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MarketplaceTrxExport implements FromArray, WithHeadings{

    public function headings(): array
    {
        return [
            ['SL', 'TRX','SENDER TYPE','SENDER','RECEIVER TYPE','RECEIVER','SENDER AMOUNT','RECEIVER AMOUNT','CHARGE','PAYABLE','STATUS','TIME'],
        ];
    }

    public function array(): array
    {
        return Transaction::with(
            'user:id,firstname,lastname,email,username,full_mobile',
              'currency:id,name',
          )->where('type', PaymentGatewayConst::MARKETPLACE)->where('attribute',PaymentGatewayConst::SEND)->latest()->get()->map(function($item,$key){
            if($item->user_id != null){
                $user_type =  "USER"??"";
               
            }

            return [
                'id'    => $key + 1,
                'trx'  => $item->trx_id??"",
                'sender_type'  =>$user_type??"",
                'sender'  => $item->creator->email??"",
                'receiver_type'  => $user_type??"",
                'receiver'  => $item->trade->user->email,
                'amount'  =>  get_amount($item->trade->amount, $item->trade->saleCurrency->code,get_wallet_precision($item->trade->saleCurrency)) ,
                'will_get'  =>  get_amount($item->request_amount, $item->trade->rateCurrency->code,get_wallet_precision($item->trade->rateCurrency)),
                'charge_amount'  =>  get_amount($item->charge->total_charge,$item->trade->rateCurrency->code??get_default_currency_code(),get_wallet_precision($item->trade->rateCurrency)),
                'payable_amount'  =>  get_amount(@$item->payable+@$item->charge->total_charge,@$item->trade->rateCurrency->code, get_wallet_precision(@$item->trade->rateCurrency)),
                'status'  => __( $item->stringStatus->value),
                'time'  =>   $item->created_at->format('d-m-y h:i:s A'),
            ];
         })->toArray();

    }
}

