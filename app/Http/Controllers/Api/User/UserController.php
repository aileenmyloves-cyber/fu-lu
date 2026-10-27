<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\GiftCard;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\VirtualCardApi;
use App\Models\UserNotification;
use App\Http\Helpers\Api\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\TransactionLimit;
use App\Models\Admin\TransactionSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Providers\Admin\BasicSettingsProvider;
use App\Traits\AdminNotifications\AuthNotifications;

class UserController extends Controller
{
    use AuthNotifications;

    protected $api;
    protected $basic_settings;
    public function __construct()
    {
        $cardApi = VirtualCardApi::first();
        $this->api =  $cardApi;
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function home(){
        $user                   = auth()->user();
        $totalAddMoney          = amountOnBaseCurrency(Transaction::auth()->addMoney()->where('status',1)->get());
        $totalReceiveRemittance = amountOnBaseCurrency(Transaction::auth()->remitance()->where('attribute',"RECEIVED")->where('status',1)->get());
        $totalSendRemittance    = amountOnBaseCurrency(Transaction::auth()->remitance()->where('attribute',"SEND")->where('status',1)->orderBy('id',"DESC")->get());
        $cardAmount             = virtualCardAmountOnBaseCurrency(userActiveCardData()['total_cards']);
        $billPay                = amountOnBaseCurrency(Transaction::auth()->billPay()->where('status',1)->get());
        $topUps                 = amountOnBaseCurrency(Transaction::auth()->mobileTopup()->where('status',1)->get());
        $totalTransactions      = Transaction::auth()->where('status', 1)->count();
        $total_gift_cards       = GiftCard::auth()->count();
        $withdraw_amount        = amountOnBaseCurrency(Transaction::auth()->moneyOut()->where('status',1)->get());

        $transaction_types = [
            'add_money'         => PaymentGatewayConst::TYPEADDMONEY,
            'money_out'         => PaymentGatewayConst::TYPEMONEYOUT,
            'transfer_money'    => PaymentGatewayConst::TYPETRANSFERMONEY,
            'exchange_money'    => PaymentGatewayConst::TYPEMONEYEXCHANGE,
            'money_in'          => PaymentGatewayConst::MONEYIN,
            'agent_money_out'   => PaymentGatewayConst::AGENTMONEYOUT,
            'request_money'     => PaymentGatewayConst::REQUESTMONEY,
            'pay_link'          => PaymentGatewayConst::TYPEPAYLINK,
            'pay_user_pay_link' => PaymentGatewayConst::PAYMENTPAYLINK,
            'bill_pay'          => PaymentGatewayConst::BILLPAY,
            'mobile_top_up'     => PaymentGatewayConst::MOBILETOPUP,
            'virtual_card'      => PaymentGatewayConst::VIRTUALCARD,
            'remittance'        => PaymentGatewayConst::SENDREMITTANCE,
            'merchant-payment'  => PaymentGatewayConst::MERCHANTPAYMENT,
            'make_payment'      => PaymentGatewayConst::TYPEMAKEPAYMENT,
            'gift_cards'        => PaymentGatewayConst::GIFTCARD,
            'add_sub_balance'   => PaymentGatewayConst::TYPEADDSUBTRACTBALANCE,
            'refer_bonus'       => PaymentGatewayConst::TYPEREFERBONUS,
            'register_bonus'    => PaymentGatewayConst::TYPEBONUS,
            'trade'             => PaymentGatewayConst::TRADE,
            'marketplace'       => PaymentGatewayConst::MARKETPLACE,

        ];

        $transactions = Transaction::auth()->latest()->take(5)->get()->map(function($item){
            if($item->type == payment_gateway_const()::TYPEADDMONEY){
                return[
                    'id'               => $item->id,
                    'type'             => PaymentGatewayConst::RECEIVED,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => isCrypto($item->payable,$item->currency->currency_code??get_default_currency_code(),$item->currency->gateway->crypto),
                    'status'           => $item->stringStatus->value,
                    'remark'           => $item->remark??"",
                    'date_time'        => $item->created_at,

                ];
            }elseif($item->type == payment_gateway_const()::BILLPAY){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,billPayCurrency($item)['wallet_currency'],get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->payable,billPayCurrency($item)['wallet_currency'],get_wallet_precision($item->creator_wallet->currency)),
                    'status'           => $item->stringStatus->value,
                    'remark'           => $item->remark??"",
                    'date_time'        => $item->created_at,

                ];
            }elseif($item->type == payment_gateway_const()::MERCHANTPAYMENT){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency??null)),
                    'payable'          => get_amount($item->details->charges->payable,$item->details->charges->payable_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                    'status'           => $item->stringStatus->value,
                    'remark'           => $item->remark??"",
                    'date_time'        => $item->created_at,
                ];

            }elseif($item->type == payment_gateway_const()::MOBILETOPUP){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,topUpCurrency($item)['wallet_currency'],get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->payable,topUpCurrency($item)['wallet_currency'],get_wallet_precision($item->creator_wallet->currency)),
                    'status'           => $item->stringStatus->value,
                    'remark'           => $item->remark??"",
                    'date_time'        => $item->created_at,
                ];

            }elseif($item->type == payment_gateway_const()::TYPEMONEYOUT){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,withdrawCurrency($item)['wallet_currency'],get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->details->charges->payable??$item->request_amount,withdrawCurrency($item)['wallet_currency'],get_wallet_precision($item->creator_wallet->currency)),
                    'status'           => $item->stringStatus->value,
                    'remark'           => $item->remark??"",
                    'date_time'        => $item->created_at,
                ];

            }elseif($item->type == payment_gateway_const()::SENDREMITTANCE){
                if($item->attribute == payment_gateway_const()::SEND){
                    if(@$item->details->remitance_type == Str::slug(GlobalConst::TRX_WALLET_TO_WALLET_TRANSFER)){
                        return[
                            'id'               => @$item->id,
                            'type'             => $item->attribute,
                            'trx'              => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'request_amount'   => get_amount(@$item->request_amount,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                            'payable'          => get_amount(@$item->payable,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                            'status'           => @$item->stringStatus->value,
                            'remark'           => $item->remark??"",
                            'date_time'        => @$item->created_at,
                        ];
                    }elseif(@$item->details->remitance_type == Str::slug(GlobalConst::TRX_BANK_TRANSFER)){
                        return[
                            'id'               => @$item->id,
                            'type'             => $item->attribute,
                            'trx'              => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'request_amount'   => get_amount(@$item->request_amount,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                            'payable'          => get_amount(@$item->payable,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                            'status'           => @$item->stringStatus->value,
                            'remark'           => $item->remark??"",
                            'date_time'        => @$item->created_at,
                        ];
                    }elseif(@$item->details->remitance_type == Str::slug(GlobalConst::TRX_CASH_PICKUP)){
                        return[
                            'id'               => @$item->id,
                            'type'             => $item->attribute,
                            'trx'              => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'request_amount'   => get_amount(@$item->request_amount,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                            'payable'          => get_amount(@$item->payable,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                            'status'           => @$item->stringStatus->value,
                            'remark'           => $item->remark??"",
                            'date_time'        => @$item->created_at,
                        ];
                    }

                }elseif($item->attribute == payment_gateway_const()::RECEIVED){
                    return[
                        'id'               => @$item->id,
                        'type'             => $item->attribute,
                        'trx'              => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount(@$item->request_amount,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount(@$item->payable,$item->details->charges->sender_cur_code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'status'           => @$item->stringStatus->value,
                        'remark'           => $item->remark??"",
                        'date_time'        => @$item->created_at,

                    ];

                }

            }elseif($item->type == payment_gateway_const()::TYPETRANSFERMONEY){
                if($item->attribute == payment_gateway_const()::SEND){
                    return[
                        'id'               => @$item->id,
                        'type'             => $item->attribute,
                        'trx'              => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->details->charges->sender_amount??$item->request_amount,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charges->payable??$item->payable,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'remark'           => $item->remark??"",
                        'status'           => @$item->stringStatus->value,
                        'date_time'        => @$item->created_at,
                    ];
                }elseif($item->attribute == payment_gateway_const()::RECEIVED){
                    return[
                        'id'               => @$item->id,
                        'type'             => $item->attribute,
                        'trx'              => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->details->charges->sender_amount??$item->request_amount,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charges->payable??$item->payable,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'status'           => @$item->stringStatus->value,
                        'remark'           => $item->remark??"",
                        'date_time'        => @$item->created_at,
                    ];

                }

            }elseif($item->type == payment_gateway_const()::VIRTUALCARD){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->details->charges->card_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->payable,$item->details->charges->from_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                    'status'           => $item->stringStatus->value,
                    'remark'           => $item->remark??"",
                    'date_time'        => $item->created_at,

                ];

            }elseif($item->type == payment_gateway_const()::TYPEMAKEPAYMENT){

                if($item->attribute == payment_gateway_const()::SEND){
                    return[
                        'id'               => @$item->id,
                        'type'             => $item->attribute,
                        'trx'              => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->details->charges->sender_amount??$item->request_amount,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charges->payable??$item->payable,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                        'remark'           => $item->remark??"",
                        'status'           => @$item->stringStatus->value,
                        'date_time'        => @$item->created_at,
                    ];
                }elseif($item->attribute == payment_gateway_const()::RECEIVED){
                    return[
                       'id'               => @$item->id,
                       'type'             => $item->attribute,
                       'trx'              => @$item->trx_id,
                       'transaction_type' => $item->type,
                       'request_amount'   => get_amount($item->details->charges->sender_amount??$item->request_amount,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                       'payable'          => get_amount($item->details->charges->payable??$item->payable,$item->details->charges->sender_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                       'remark'           => $item->remark??"",
                       'status'           => @$item->stringStatus->value,
                       'date_time'        => @$item->created_at,
                    ];

                }

            }elseif($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_transaction_numeric_attribute($item->attribute).get_amount($item->request_amount,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount(@$item->payable,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'remark'           => $item->remark??"",
                    'status'           => $item->stringStatus->value,
                    'date_time'        => $item->created_at,

                ];

            }elseif($item->type == payment_gateway_const()::TYPEPAYLINK){
                if($item->attribute == payment_gateway_const()::RECEIVED){
                    return[
                        'id'               => $item->id,
                        'type'             => $item->attribute,
                        'trx'              => $item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->request_amount,@$item->details->charge_calculation->receiver_currency_code,get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charge_calculation->conversion_payable??$item->details->charge_calculation->receiver_amount,@$item->details->charge_calculation->receiver_currency_code,get_wallet_precision($item->creator_wallet->currency)),
                        'remark'           => $item->remark??"",
                        'status'           => $item->stringStatus->value,
                        'date_time'        => $item->created_at,
                    ];
                }elseif($item->attribute == payment_gateway_const()::SEND){
                    return[
                        'id'               => $item->id,
                        'type'             => $item->attribute,
                        'trx'              => $item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->request_amount,@$item->details->charge_calculation->receiver_currency_code,get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charge_calculation->conversion_payable??$item->details->charge_calculation->receiver_amount,@$item->details->charge_calculation->receiver_currency_code,get_wallet_precision($item->creator_wallet->currency)),
                        'remark'           => $item->remark??"",
                        'status'           => $item->stringStatus->value,
                        'date_time'        => $item->created_at,
                    ];
                }

            }elseif($item->type == payment_gateway_const()::REQUESTMONEY){
                if($item->attribute == payment_gateway_const()::SEND){
                    return[
                        'id'               => $item->id,
                        'type'             => $item->attribute,
                        'trx'              => $item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->details->charges->request_amount,$item->details->charges->sender_currency,get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charges->request_amount,$item->details->charges->sender_currency,get_wallet_precision($item->creator_wallet->currency)),
                        'remark'           => $item->remark??"",
                        'status'           => $item->stringStatus->value,
                        'date_time'        => $item->created_at,
                    ];
                }else{
                    return[
                        'id'               => $item->id,
                        'type'             => $item->attribute,
                        'trx'              => $item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount'   => get_amount($item->details->charges->receiver_amount,$item->details->charges->receiver_currency,get_wallet_precision($item->creator_wallet->currency)),
                        'payable'          => get_amount($item->details->charges->payable,$item->details->charges->receiver_currency,get_wallet_precision($item->creator_wallet->currency)),
                        'remark'           => $item->remark??"",
                        'status'           => $item->stringStatus->value,
                        'date_time'        => $item->created_at,
                    ];
                }

            }elseif($item->type == payment_gateway_const()::AGENTMONEYOUT){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->details->charges->sender_amount,$item->details->charges->sender_currency,get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->details->charges->payable,$item->details->charges->sender_currency,get_wallet_precision($item->creator_wallet->currency)),
                    'remark'           => $item->remark??"",
                    'status'           => $item->stringStatus->value,
                    'date_time'        => $item->created_at,
                ];

            }elseif($item->type == payment_gateway_const()::MONEYIN){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->request_amount,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'remark'           => $item->remark??"",
                    'status'           => $item->stringStatus->value,
                    'date_time'        => $item->created_at,
                ];
            }elseif($item->type == payment_gateway_const()::GIFTCARD){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->details->card_info->user_wallet_currency,get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->payable,$item->details->card_info->user_wallet_currency,get_wallet_precision($item->creator_wallet->currency)),
                    'remark'           => $item->remark??"",
                    'status'           => $item->stringStatus->value,
                    'date_time'        => $item->created_at,
                ];
            }elseif($item->type == payment_gateway_const()::TYPEMONEYEXCHANGE){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'payable'          => get_amount($item->payable,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                    'remark'           => $item->remark??"",
                    'status'           => $item->stringStatus->value,
                    'date_time'        => $item->created_at,
                ];
            }elseif($item->type == payment_gateway_const()::TYPEREFERBONUS){
                return[
                    'id'               => $item->id,
                    'type'             => $item->attribute,
                    'trx'              => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount'   => get_amount($item->request_amount,$item->details->request_currency),
                    'payable'          => get_amount($item->request_amount,$item->details->request_currency),
                    'remark'           => $item->remark??"",
                    'status'           => $item->stringStatus->value,
                    'date_time'        => $item->created_at,
                ];
            }elseif($item->type == payment_gateway_const()::TYPEBONUS){
                return[
                    'id' => $item->id,
                    'type' =>$item->attribute,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => get_amount($item->request_amount,$item->details->request_currency),
                    'payable'       => get_amount($item->request_amount,$item->details->request_currency),
                    'remark' => $item->remark??"",
                    'status' => $item->stringStatus->value ,
                    'date_time' => $item->created_at ,
                ];
            }elseif($item->type == payment_gateway_const()::TRADE){
                return[
                    'id'    => $item->id,
                    'type'  =>$item->attribute,
                    'trx'   => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => get_amount($item->request_amount,$item->trade->saleCurrency->code, get_wallet_precision($item->trade->saleCurrency)),
                    'payable'       => get_amount($item->trade->rate,$item->trade->rateCurrency->code, get_wallet_precision($item->trade->rateCurrency)),
                    'remark' => $item->remark??"",
                    'status' => $item->stringStatus->value ,
                    'date_time' => $item->created_at ,
                ];
            }elseif($item->type == payment_gateway_const()::MARKETPLACE){
                $attribute = $item->attribute == PaymentGatewayConst::SEND ? __('Buy') : __('Sale');

                if (auth()->user()->id == $item->user_id && $item->attribute == PaymentGatewayConst::SEND) {
                    $amount = get_amount($item->trade->rate, $item->trade->rateCurrency->code, get_wallet_precision($item->trade->rateCurrency));
                    $payable = get_amount($item->trade->rate+$item->charge->total_charge, $item->trade->rateCurrency->code, get_wallet_precision($item->trade->rateCurrency));
                    return[
                        'id'    => $item->id,
                        'type'  =>$attribute,
                        'trx'   => $item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount' => $amount,
                        'payable'       => $payable,
                        'remark' => $item->remark??"",
                        'status' => $item->stringStatus->value ,
                        'date_time' => $item->created_at ,
                    ];

                } else {
                    $amount = get_amount($item->trade->amount, $item->trade->saleCurrency->code, get_wallet_precision($item->trade->saleCurrency));
                    $payable = get_amount($item->request_amount, $item->trade->rateCurrency->code, get_wallet_precision($item->trade->rateCurrency));

                    return[
                        'id'    => $item->id,
                        'type'  =>$attribute,
                        'trx'   => $item->trx_id,
                        'transaction_type' => $item->type,
                        'request_amount' => $amount,
                        'payable'       => $payable,
                        'remark' => $item->remark??"",
                        'status' => $item->stringStatus->value ,
                        'date_time' => $item->created_at ,
                    ];
                }
            }
        });

        //module access permissions
        $module_access =[
            'send_money'            => module_access_api('send-money'),
            'receive_money'         => module_access_api('receive-money'),
            'remittance_money'      => module_access_api('remittance-money'),
            'add_money'             => module_access_api('add-money'),
            'withdraw_money'        => module_access_api('withdraw-money'),
            'make_payment'          => module_access_api('make-payment'),
            'virtual_card'          => module_access_api('virtual-card'),
            'bill_pay'              => module_access_api('bill-pay'),
            'mobile_top_up'         => module_access_api('mobile-top-up'),
            'request_money'         => module_access_api('request-money'),
            'pay_link'              => module_access_api('pay-link'),
            'money_out'             => module_access_api('money-out'),
            'gift_cards'            => module_access_api('gift-cards'),
            'money_exchange'        => module_access_api('money-exchange'),
            'trade'                 => module_access_api('trade'),
            'my_chat'               => module_access_api('my-chat'),
        ];

        $cardCreateCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->get()->map(function($data){
            return [
                'id'                        => $data->id,
                'slug'                      => $data->slug,
                'title'                     => $data->title,
                'fixed_charge'              => get_amount($data->fixed_charge,null,get_wallet_precision()),
                'percent_charge'            => get_amount($data->percent_charge,null,get_wallet_precision()),
                'min_limit'                 => get_amount($data->min_limit,null,get_wallet_precision()),
                'max_limit'                 => get_amount($data->max_limit,null,get_wallet_precision()),
                'daily_limit'               => get_amount($data->daily_limit,null,get_wallet_precision()),
                'monthly_limit'             => get_amount($data->monthly_limit,null,get_wallet_precision()),
            ];
        })->first();

        $cardReloadCharge = TransactionSetting::where('slug','reload_card')->where('status',1)->get()->map(function($data){
            return [
                'id'                        => $data->id,
                'slug'                      => $data->slug,
                'title'                     => $data->title,
                'fixed_charge'              => get_amount($data->fixed_charge,null,get_wallet_precision()),
                'percent_charge'            => get_amount($data->percent_charge,null,get_wallet_precision()),
                'min_limit'                 => get_amount($data->min_limit,null,get_wallet_precision()),
                'max_limit'                 => get_amount($data->max_limit,null,get_wallet_precision()),
                'daily_limit'               => get_amount($data->daily_limit,null,get_wallet_precision()),
                'monthly_limit'             => get_amount($data->monthly_limit,null,get_wallet_precision()),
            ];
        })->first();

        $basic_settings = BasicSettingsProvider::get();
        if(!$basic_settings) {
            $message = ['error'=>[__("Basic setting not found!")]];
            return Helpers::error($message);
        }
        $notification_config = $basic_settings->push_notification_config;

        if(!$notification_config) {
            $message = ['error'=>[__("Notification configuration not found!")]];
            return Helpers::error($message);
        }

        $pusher_credentials = [
            "instanceId" => $notification_config->instance_id ?? '',
            "secretKey" => $notification_config->primary_key ?? '',
        ];
        $broadcast_config = $basic_settings->broadcast_config;

        $pusher_broadcast_config = [
            "method"          => $broadcast_config->method ?? '',
            "app_id"          => $broadcast_config->app_id ?? '',
            "primary_key"     => $broadcast_config->primary_key ?? '',
            "secret_key"      => $broadcast_config->secret_key ?? '',
            "cluster"         => $broadcast_config->cluster ?? '',
            "my_chat_channel" => 'support.chat.conversation.{$chat_id}',
            "my_chat_event"   => 'conversation',
        ];


        $data =[
            'base_curr'               => get_default_currency_code(),
            'pusher_credentials'      => (object)$pusher_credentials,
            'pusher_broadcast_config' => (object)$pusher_broadcast_config,
            'pin_verification'        => $this->basic_settings->user_pin_verification,
            'notification_id'         => make_user_id_for_pusher("user", $user->id),
            'module_access'           => (object)$module_access,
            'active_virtual_system'   => activeCardSystem(),
            'card_create_charge'      => $cardCreateCharge,
            'card_reload_charge'      => $cardReloadCharge,
            'userWallets'             => user_wallets(authGuardApi(),'user_id'),
            'default_image'           => "backend/images/default/profile-default.webp",
            "image_path"              => "frontend/user",
            'user'                    => $user,
            'totalAddMoney'           => get_amount($totalAddMoney,get_default_currency_code(),get_wallet_precision()),
            'withdraw_amount'         => get_amount($withdraw_amount,get_default_currency_code(),get_wallet_precision()),
            'totalReceiveRemittance'  => get_amount($totalReceiveRemittance,get_default_currency_code(),get_wallet_precision()),
            'totalSendRemittance'     => get_amount($totalSendRemittance,get_default_currency_code(),get_wallet_precision()),
            'cardAmount'              => get_amount($cardAmount,get_default_currency_code(),get_wallet_precision()),
            'billPay'                 => get_amount($billPay,get_default_currency_code(),get_wallet_precision()),
            'topUps'                  => get_amount($topUps,get_default_currency_code(),get_wallet_precision()),
            'totalTransactions'       => $totalTransactions,
            'totalGiftCards'          => $total_gift_cards,
            'transaction_types'       => $transaction_types,
            'transactions'            => $transactions,
        ];
        $message =  ['success'=>[__('User Dashboard')]];
        return Helpers::success($data,$message);
    }
    public function profile(){
        $user = auth()->user();
        $data =[
           'base_url'      => get_asset_url('/'),
           'default_image' => files_asset_path_basename("default"),
           "image_path"    => files_asset_path_basename('user-profile'),
           'user'          => $user,
        ];
        $message =  ['success'=>[__('User Profile')]];
        return Helpers::success($data,$message);
    }
    public function profileUpdate(Request $request){
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'firstname'     => "required|string|max:60",
            'lastname'      => "required|string|max:60",
            'email'         =>  $user->registered_by == GlobalConst::EMAIL ? "nullable": "required|email|max:100",
            'country'       =>  $user->registered_by == GlobalConst::PHONE ? "nullable": "required|string|max:50",
            'phone_code'    =>  $user->registered_by == GlobalConst::PHONE ? "nullable": "required|string|max:20",
            'phone'         =>  $user->registered_by == GlobalConst::PHONE ? "nullable": "required|string|max:20|unique:users,mobile,".$user->id,
            'state'         => "nullable|string|max:50",
            'city'          => "nullable|string|max:50",
            'zip_code'      => "nullable|string",
            'address'       => "nullable|string|max:250",
            'image'         => "nullable|image|mimes:jpg,png,svg,webp|max:10240",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $data = $request->all();
        $mobileCode = remove_speacial_char($data['phone_code']);
        $mobile = remove_speacial_char($data['phone']);

        $validated['firstname']      =$data['firstname'];
        $validated['lastname']      =$data['lastname'];
        $validated['mobile']        = $user->registered_by == GlobalConst::PHONE ? $user->mobile : remove_special_char($data['phone']);
        $validated['mobile_code']   = $user->registered_by == GlobalConst::PHONE ? $user->mobile_code : remove_special_char($data['phone_code']);
        $complete_phone             = $mobileCode.$mobile;
        $validated['full_mobile']   = $complete_phone;
        $validated['email']         = $user->registered_by == GlobalConst::EMAIL ? $user->email : $data['email'];


        $validated['address']       = [
            'country'   => $user->registered_by == GlobalConst::PHONE ? $user->address->country ??"" : $data['country'],
            'state'     => $data['state'] ?? "",
            'city'      => $data['city'] ?? "",
            'zip'       => $data['zip_code'] ?? "",
            'address'   => $data['address'] ?? "",
        ];
        if($request->hasFile("image")) {
            if($user->image == 'default.png'){
                $oldImage = null;
            }else{
                $oldImage = $user->image;
            }
            $image = upload_file($data['image'],'user-profile', $oldImage);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']],'user-profile');
            delete_file($image['dev_path']);
            $validated['image']     = $upload_image;
        }

        try{
            $user->update($validated);
        }catch(Exception $e) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        $message =  ['success'=>[__("Profile successfully updated!")]];
        return Helpers::onlysuccess($message);
    }
    public function passwordUpdate(Request $request) {
        $basic_settings = BasicSettingsProvider::get();
        $passowrd_rule = "required|string|min:6|confirmed";
        if($basic_settings->secure_password) {
            $passowrd_rule = ["required",Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),"confirmed"];
        }
        $validator = Validator::make($request->all(), [
            'current_password'      => "required|string",
            'password'              => $passowrd_rule,
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        if(!Hash::check($request->current_password,auth()->user()->password)) {
            $error = ['error'=>[__("Current password didn't match")]];
            return Helpers::error($error);
        }

        try{
            auth()->user()->update([
                'password'  => Hash::make($request->password),
            ]);
        }catch(Exception $e) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        $message =  ['success'=>[__('Password successfully updated!')]];
        return Helpers::onlysuccess($message);

    }
    public function deleteAccount(Request $request) {
        $user = auth()->user();
        //make unsubscribe
         try{
            (new PushNotificationHelper(['users' => [$user->id],'user_type' => 'user']))->unsubscribe();
        }catch(Exception $e) {}
        //admin notification
        $this->deleteUserNotificationToAdmin($user,"USER",'api');
        $user->status = false;
        $user->email_verified = false;
        $user->kyc_verified = false;
        $user->deleted_at = now();
        $user->save();
        try{
            $user->token()->revoke();
            $message =  ['success'=>[__('Your profile deleted successfully!')]];
            return Helpers::onlysuccess($message);
        }catch(Exception $e) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
    }
    public function notifications(){
        $notifications = UserNotification::auth()->latest()->get()->map(function($item){
            return[
                'id' => $item->id,
                'type' => $item->type,
                'title' => $item->message->title??"",
                'message' => $item->message->message??"",
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,

            ];
        });
        $data =[
            'notifications'  => $notifications
        ];
        $message =  ['success'=>[__('User Notifications')]];
        return Helpers::success($data,$message);
    }
    public function getWallets(){
        $userWallets = user_wallets(authGuardApi(),'user_id');
        $data =[
            'userWallets'  => $userWallets
        ];
        $message =  ['success'=>[__('All Wallets')]];
        return Helpers::success($data,$message);
    }
    public function getRemainingBalance(){
        $validator = Validator::make(request()->all(), [
            'transaction_type' => "required|string",
            'attribute'        => "required|string",
            'sender_amount'    => "sometimes|required",
            'currency_code'    => "required|string|exists:currencies,code",
            'charge_id'        => "required|integer",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validate();
        $sender_wallet = UserWallet::auth()->active()->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['currency_code'])->active();
        })->active()->first();
        if(!$sender_wallet){
            $error = ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }

        if($validated['transaction_type'] == PaymentGatewayConst::TYPEMONEYOUT || $validated['transaction_type'] == PaymentGatewayConst::TYPEADDMONEY){
            $limits = PaymentGatewayCurrency::where('id',$validated['charge_id'])->first();
        }else{
            if( $validated['transaction_type']  == payment_gateway_const()::VIRTUALCARD && activeCardSystem() == 'cardyfie'){
                $cardApi = VirtualCardApi::first();
                $limits = (object)[
                    'id'            => 1,
                    'daily_limit'   => floatval($cardApi->config->cardyfie_daily_limit ?? 0),
                    'monthly_limit' => floatval($cardApi->config->cardyfie_monthly_limit ?? 0),
                ];
            }else{
                $limits = TransactionSetting::where('id',$validated['charge_id'])->first();
            }
        }

        try{
            $result = (new TransactionLimit())->trxLimit('user_id',$sender_wallet->user->id,$validated['transaction_type'],$sender_wallet->currency,$validated['sender_amount'],$limits,$validated['attribute'],'json');

            $data = [
                'status'            =>  $result['status'],
                'transaction_type'  =>  $result['transaction_type'],
                'remainingDaily'    =>  $result['data']['remainingDailyTxnSelected'],
                'remainingMonthly'  =>  $result['data']['remainingMonthlyTxnSelected'],
                'currency'          =>  $sender_wallet->currency->code ?? get_default_currency_code(),
            ];

            if($data['status'] == true){
                $message =  ['success'=>[__($result['message']) ?? ""]];
                return Helpers::success($data,$message);
            }else{
                $error = ['error'=>[__($result['message']) ?? ""]];
            return Helpers::error($error,$data);
            }
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            $error = ['error'=>[__($errorData['message'] ?? __("Something went wrong! Please try again."))]];
            return Helpers::error($error);
            return Helpers::error($error);
        }
    }
    public function pinVerify(Request $request){
        $validator      = Validator::make($request->all(),[
            'pin'               =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated  = $validator->validated();
        $user       = authGuardApi()['user'];
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false) {
                $error = ['error'=>[$pin_status['message']]];
                return Helpers::error($error);
            }
        }
        $data =[
            'match_status' => true
        ];
        $message =  ['success'=>[__('PIN verification successful.')]];
        return Helpers::success($data,$message);

    }
}