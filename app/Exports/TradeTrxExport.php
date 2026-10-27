<?php

namespace App\Exports;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TradeTrxExport implements FromArray, WithHeadings{

    public function headings(): array
    {
        return [
            ['SL', 'TRX','SENDER TYPE','SENDER','SELLING AMOUNT','ASKING AMOUNT','CHARGE','PAYABLE','STATUS','TIME'],
        ];
    }

    public function array(): array
    {
        return Transaction::with(
            'user:id,firstname,lastname,email,username,full_mobile',
              'currency:id,name',
          )->where('type', PaymentGatewayConst::TRADE)->where('attribute',PaymentGatewayConst::SEND)->latest()->get()->map(function($item,$key){
            if($item->user_id != null){
                $user_type =  "USER"??"";

            }

            return [
                'id'    => $key + 1,
                'trx'  => $item->trx_id??"",
                'sender_type'  =>$user_type??"",
                'sender'  => $item->creator->email??"",
                'amount'  =>  get_amount($item->trade->amount, $item->trade->saleCurrency->code,get_wallet_precision($item->trade->saleCurrency)),
                'will_get'  =>  get_amount($item->trade->rate, $item->trade->rateCurrency->code,get_wallet_precision($item->trade->rateCurrency)),
                'charge_amount'  =>  get_amount($item->charge->total_charge,$item->trade->saleCurrency->code??get_default_currency_code(),get_wallet_precision($item->trade->saleCurrency)),
                'payable_amount'  =>  get_amount(@$item->payable+@$item->charge->total_charge,@$item->trade->saleCurrency->code, get_wallet_precision(@$item->trade->saleCurrency)),
                'status'  => __( $item->tradeStringStatus->value),
                'time'  =>   $item->created_at->format('d-m-y h:i:s A'),
            ];
         })->toArray();

    }
}

