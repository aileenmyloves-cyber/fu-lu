<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\User;
use App\Models\Trade;
use App\Models\TradeOffer;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\NotificationHelper;
use App\Models\Admin\TransactionSetting;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Providers\Admin\BasicSettingsProvider;
use App\Notifications\User\Trade\TradeOfferMail;
use App\Traits\PaymentGateway\ManualCrowPayment;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\Trade\MarketplaceMail;
use App\Notifications\User\Trade\MarketplaceMailSender;
use App\Notifications\User\Trade\MarketplaceMailReceiver;

class TradeOfferController extends Controller
{
    use ControlDynamicInputFields, ManualCrowPayment;

    protected  $trx_id;
    protected $basic_settings;


    public function __construct()
    {
        $this->trx_id = 'MP'.getTrxNum();
        $this->basic_settings = BasicSettingsProvider::get();
    }

       /**
     * Get offer page show
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function index(){

        $offers = TradeOffer::with('trades','saleCurrency','rateCurrency','creator','receiver')
                            ->whereHas('creator',function($q){
                                $q->where('status', 1);
                            })
                            ->whereHas('receiver',function($q){
                                $q->where('status', 1);
                            })
                            ->where('creator_id', Auth::id())
                            ->orWhere('receiver_id', Auth::id())
                            ->orderBy('id', 'desc')
                            ->paginate(12);

        $user_id = Auth::guard(get_auth_guard())->user()->id;

        $get_offers = TradeOffer::with('trades','saleCurrency','rateCurrency','creator','receiver')
                                    ->whereHas('creator',function($q){
                                        $q->where('status', 1);
                                    })
                                    ->whereHas('receiver',function($q){
                                        $q->where('status', 1);
                                    })
                                    ->where('creator_id', $user_id)
                                    ->orWhere('receiver_id', $user_id)
                                    ->orderBy('id', 'desc')
                                    ->get()
        ->map(function ($item) use ($offers){
            $status_info = [
                '1' => 'Accept',
                '2' => 'Pending',
                '3' => 'Sold',
                '4' => 'Rejected',
            ];
            $trade_status_info = [
                "Ongoing"         => 1,
                "Pending"         => 2,
                "Rejected"        => 4,
                "Payment Pending" => 5,
                "Complete"        => 6,
                'Close Requested' => 7,
                'Closed'          => 8,
            ];




            $acceptStatus = "false";
            $rejectStatus = "false";
            $counterStatus = "false";
            $pay = "false";

            $auth_id = Auth::guard(get_auth_guard())->user()->id;

            if ($item->trades->status == 1) {

                if ($auth_id == $item->receiver_id) {

                    if ($item->status == 4) {
                        $statusString = __("Rejected");
                    }elseif($item->status == 1) {
                        if ($auth_id == $item->trade_user_id) {
                            $statusString = __("Accepted");
                        } else {
                            $pay = 'true';
                        }

                    }else {
                        if ($offers->where('receiver_id',$auth_id)->first()->id == $item->id) {

                            $acceptStatus = "true";
                            $rejectStatus = "true";
                            $counterStatus = "true";
                        }
                    }
                } else {

                    if ($item->status == 4) {
                        $statusString = __("Rejected");
                    }elseif ($item->status == 1) {
                        if ($auth_id == $item->trade_user_id) {
                            $statusString = __("Accepted");
                        } else {
                            $pay = 'true';
                        }
                    }

                }


            } else {
                $statusString = __("Sold");
            }




            return[
                'id'                 => $item->id,
                'type'               => $item->type ?? "",
                'amount'             => get_amount($item->amount,null,get_wallet_precision($item->saleCurrency)),
                'sale_currency_code' => $item->saleCurrency->code ?? "",
                'rate'               => get_amount($item->rate,null,get_wallet_precision($item->rateCurrency)),
                'rate_currency_code' => $item->rateCurrency->code ?? "",
                'creator_id'         => $item->creator_id ?? "",
                'trade_user_id'        => $item->trade_user_id ?? "",
                'receiver_id'        => $item->receiver_id ?? "",
                'trade_id '          => $item->trade_id ?? "",
                'status_info'        => $status_info ?? "",
                'status'             => $item->status ?? "",
                'offer_created'      => $item->created_at ?? "",
                'creator_image'      => $item->creator->image ?? "",
                'email_verified'     => $item->creator->email_verified ?? "",
                'kyc_verified'       => $item->creator->kyc_verified ?? "",
                'creator_name'       => $item->creator->firstname.' '. $item->creator->lastname,
                'trade_amount'       => get_amount($item->trades->amount,null,get_wallet_precision($item->trades->saleCurrency)),
                'trade_rate'         => get_amount($item->trades->rate,null,get_wallet_precision($item->trades->rateCurrency)),
                'trade_status_info'  => $trade_status_info,
                'trade_status'       => $item->trades->status,
                'acceptStatus'       => $acceptStatus,
                'rejectStatus'       => $rejectStatus,
                'counterStatus'      => $counterStatus,
                'pay'                => $pay,
                'statusString'        => $statusString ?? "",

            ];
        });

        $data = [
            'default_image' => "backend/images/default/profile-default.webp",
            "image_path"    => "frontend/user",
            'get_offers'    => $get_offers,
        ];



        $message = ['success' => [__('Data fetch successfully').'!']];
        return Helpers::success($data,$message);
    }



    public function offerSubmit(Request $request){

        $validator = Validator::make($request->all(), [
            'rate'         => 'required',
            'trade_id' => 'required',
            'type'         => 'required',
            'receiver_id'  => 'nullable',
            'creator_id'  =>  'nullable',
        ]);

        $user_id = Auth::guard(get_auth_guard())->user()->id;

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();
        $trade = Trade::find($validated['trade_id']);

        if(!$trade){
            return Helpers::error(['error' => [__('Invalid request').'!']]);
        }

        try {

            if($validated['type'] == 'COUNTER_OFFER'){

                $user_id = Auth::guard(get_auth_guard())->user()->id;

                $tradeOffer = TradeOffer::where('trade_id', $trade->id)->orderBy('id','desc')->first();
                if($tradeOffer == null){
                    return Helpers::error(['error' => [__('No offer found').'!']]);
                }

                if($validated['receiver_id'] == $user_id){
                    $receiver_id = $validated['creator_id'];
                }else{
                    $receiver_id = $validated['receiver_id'];
                }
            }else{
                $receiver_id = $trade->user_id;
                if($trade->user_id == $user_id){
                    return Helpers::error(['error' => [__('Can not send offer request for your trade')]]);
                }
            }


            $user = User::where('id', $receiver_id)->first();
            if(!$user){
                return Helpers::error(['error' => [__('User not found')]]);
            }

            TradeOffer::create([
                'type'             => $validated['type'],
                'trade_id'         => $trade->id,
                'creator_id'       => $user_id,
                'receiver_id'      => $receiver_id,
                'trade_user_id'      => $trade->user_id,
                'sale_currency_id' => $trade->currency_id,
                'amount'           => $trade->amount,
                'rate'             => $validated['rate'],
                'rate_currency_id' => $trade->rate_currency_id,
                'status'           => 2,
            ]);



            if($validated['type'] == 'COUNTER_OFFER'){
                $notification_content = [
                    'title'         => "Counter Offer",
                    'message'       => "Offer Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)). ' @ '.get_amount($validated['rate'],$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency))." Selling Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)).' @ '.get_amount($trade->rate , $trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                    'time'          => Carbon::now()->diffForHumans(),
                    'image'         => files_asset_path('profile-default'),
                ];
                UserNotification::create([
                    'type'      => NotificationConst::COUNTER_OFFER,
                    'user_id'  =>  $receiver_id,
                    'message'   => $notification_content,
                ]);
            }else{
                $notification_content = [
                    'title'         => "Offer",
                    'message'       => "Offer Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)). ' @ '.get_amount($validated['rate'],$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency))." Selling Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)).' @ '.get_amount($trade->rate , $trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                    'time'          => Carbon::now()->diffForHumans(),
                    'image'         => files_asset_path('profile-default'),
                ];
                UserNotification::create([
                    'type'      => NotificationConst::OFFER,
                    'user_id'  =>  $receiver_id,
                    'message'   => $notification_content,
                ]);
            }


            //push notification
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {
                }
            }

            $currency = Currency::find($trade->currency_id);
            $rate_currency = Currency::find($trade->rate_currency_id);
            if(!$rate_currency) return Helpers::error(['error' => [__('Receiver Currency Not Found')]]);

            $notification_data = [
                'sender_currency'   => $currency->code,
                'receiver_currency' => $rate_currency->code,
                'sender_amount'     => $trade->amount,
                'receiver_amount'   => $validated['rate'],
                'exchange_rate'     => $validated['rate'] / $trade->amount,
                'sPrecision'        => get_wallet_precision($currency),
                'rPrecision'        => get_wallet_precision($rate_currency),
            ];


            $basic_setting = BasicSettings::first();
            try{
                if( $basic_setting->email_notification == true){

                    $notifyDataSender = [
                        'trx_id'            => $trade->transaction->trx_id,
                        'title'             => $notification_content['title']."  @" . auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'sellingAmount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'askingAmount'     => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                    ];
                    //sender notifications
                    $user->notify(new TradeOfferMail($user,(object)$notifyDataSender));

                }
            }catch(Exception $e){}

            //sms notification
            try{
                //sender sms
                if($basic_setting->sms_notification == true){
                    sendSms($user,'TRADE-OFFER',[
                        'selling_amount' => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'  => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'from_user'     => auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'trx'            => $trade->transaction->trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}


        } catch (Exception $e) {
            return Helpers::error(['error' => [__('Something went wrong. Please try again')]]);
        }

        return Helpers::onlySuccess(['success' => [__('Your offer sent successfully').'!']]);
    }




    public function offerStatus(Request $request){

        $validator = Validator::make($request->all(), [
            'target' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }

        $auth_id = Auth::guard(get_auth_guard())->user()->id;
        $tradeOffer = TradeOffer::find($request->target);
        if(!$tradeOffer){
            return Helpers::error(['error' => [__('Trade offer not found').'!']]);
        }

        if($tradeOffer->creator_id == $auth_id){
            return Helpers::error(['error' => [__('You can not status change you own offer ').'!']]);
        }

        $trade = Trade::find($tradeOffer->trade_id);

        $receiver = $tradeOffer->creator;


        $validated = $validator->validated();


        if($validated['type'] == 'Reject'){
            $status = 4;
            $title = __("Offer Rejected");

            $message = ['success' => [__('Trade offer reject successfully').'!']];
        }elseif($validated['type'] == 'Accept'){
            $status = 1;
            $title = __("Offer Accepted");
            $message = ['success' => [__('Trade offer accept successfully').'!']];
        }



        $notification_content = [
            'title'         => $title,
            'message'       => "Offer Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)). ' @ '.get_amount($tradeOffer->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency))." Selling Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency)).' @ '.get_amount($trade->rate , $trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
            'time'          => Carbon::now()->diffForHumans(),
            'image'         => files_asset_path('profile-default'),
        ];

        UserNotification::create([
            'type'      => NotificationConst::OFFER_RECECTED,
            'user_id'  =>  $receiver->id,
            'message'   => $notification_content,
        ]);


         //push notification
         if( $this->basic_settings->push_notification == true){
            try{
                (new PushNotificationHelper())->prepare([$receiver->id],[
                    'title' => $notification_content['title'],
                    'desc'  => $notification_content['message'],
                    'user_type' => 'user',
                ])->send();
            }catch(Exception $e) {
            }
        }

        $currency = Currency::find($trade->currency_id);
        $rate_currency = Currency::find($trade->rate_currency_id);
        if(!$rate_currency) return Helpers::error(['error' => [__('Receiver Currency Not Found')]]);

        $notification_data = [
            'sender_currency'   => $currency->code,
            'receiver_currency' => $rate_currency->code,
            'sender_amount'     => $trade->amount,
            'receiver_amount'   => $tradeOffer->rate,
            'exchange_rate'     => $tradeOffer->rate / $trade->amount,
            'sPrecision'        => get_wallet_precision($currency),
            'rPrecision'        => get_wallet_precision($rate_currency),
        ];


        $basic_setting = BasicSettings::first();
        try{
            if( $basic_setting->email_notification == true){

                $notifyDataSender = [
                    'trx_id'            => $trade->transaction->trx_id,
                    'title'             => $notification_content['title']."  @" . auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                    'sellingAmount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'askingAmount'     => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                    'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                ];
                //sender notifications
                $receiver->notify(new TradeOfferMail($receiver,(object)$notifyDataSender));

            }
        }catch(Exception $e){
        }

        try {
            $trade_offer = TradeOffer::find($validated['target']);
            if(!$trade_offer){
                return Helpers::error(['error' => [__('Trade offer is not found').'!']]);
            }

            $trade_offer->update([
                'status' => $status,
            ]);

        } catch (Exception $th) {
            return Helpers::error(['error' => [__('Something went wrong. Please try again').'!']]);
        }

        return Helpers::onlySuccess($message);
    }



    public function buy(Request $request){
        $validator = Validator::make($request->all(),[
            'target'   => 'required',
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        $trade_offer = TradeOffer::where('id', $validated['target'])->first();

        $trade = Trade::with('saleCurrency','rateCurrency')->where('id', $trade_offer->trade_id)->get()->map(function ($item) use ($trade_offer){
            $userWallet = UserWallet::with('currency')->where('user_id', Auth::id())->where('currency_id',$item->rate_currency_id)->first();

            $sale_currency = [
                'id'     => $item->saleCurrency->id,
                'code'   => $item->saleCurrency->code,
                'symbol' => $item->saleCurrency->symbol,
                'flag'   => $item->saleCurrency->flag ?? '',
                'rate'   => get_amount($item->saleCurrency->rate, null, get_wallet_precision($item->saleCurrency)),
            ];
            $rate_currency = [
                'id'     => $item->rateCurrency->id,
                'code'   => $item->rateCurrency->code,
                'symbol' => $item->rateCurrency->symbol,
                'flag'   => $item->rateCurrency->flag ?? '',
                'rate'   => get_amount($item->rateCurrency->rate, null, get_wallet_precision($item->rateCurrency)),
            ];
            return[
                'id'            => $item->id,
                'amount'        => get_amount($trade_offer->amount, null, get_wallet_precision($trade_offer->saleCurrency)),
                'rate'          => get_amount($trade_offer->rate, null, get_wallet_precision($trade_offer->rateCurrency)),
                'sale_currency' => $sale_currency,
                'rate_currency' => $rate_currency,
                'user_id'       => $item->user_id,
                'userwallet' => $userWallet,
            ];
        })->first();

        if ($trade == null) {
            return Helpers::error(['error' => [__('Trade not found').'!']]);
        }

        $trade = (object) $trade;

        if(!$trade){
            return Helpers::error(['error' => [__('Invalid request').'!']]);
        }

        if($trade->user_id == Auth::guard(get_auth_guard())->user()->id){
            return Helpers::error(['error' => [__('Can not buy your Trade').'!']]);
        }

        $tradeRate = Trade::where('id', $trade->id)->first();
        if(!$tradeRate){
            return Helpers::error(['error' => [__('Trade not found').'!']]);
        }

        // Charge calcualtion
        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        $charges = feesAndChargeCalculation($intervals, $trade->rate,$tradeRate->rateCurrency->rate);
        $total_charge = $charges['fixed_charge'] + $charges['percent_charge'];

        $currency_code = $trade->sale_currency['code'];
        $payment_gatewaies = PaymentGateway::with('currencies')
                                            ->where('type', PaymentGatewayConst::MANUAL)
                                            ->where('slug', PaymentGatewayConst::receiving_method_slug())
                                            ->whereHas('currencies', function($q) use ($currency_code) {
                                                $q->where('currency_code', $currency_code);
                                            })
                                            ->get()
                                            ->map(function($item){
                                                return[
                                                    'id' => $item->id,
                                                    'name' => $item->name,
                                                ];
                                            });

        $data = [
            'payment_gatewaies' => $payment_gatewaies,
            'total_charge'      => get_amount($total_charge, null, get_wallet_precision($tradeRate->saleCurrency)),
            'trade'             => $trade,
            'target'            => $validated['target'],
        ];

        return Helpers::success($data,['success' => [__('Data fetch successfully').'!']]);

    }



       /**
     * My trade confirm
     *
     * @method POST
     * @return Illuminate\Http\Response
     */
    public function confirm(Request $request){

        $validator = Validator::make($request->all(),[
            'target'     => 'required',
            'gateway_id' => 'required',
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }


        $validated = $validator->validated();
        $payment_gateway = PaymentGateway::with('currency')->find($validated['gateway_id']);
        if(!$payment_gateway){
            return Helpers::error(['error' => [__('Payment gateway not found').'!']]);
        }

        $trade_offer = TradeOffer::where('id', $validated['target'])->first();
        if(!$trade_offer){
            return Helpers::error(['error' => [__('Trade offer not found').'!']]);
        }

        $payment_gateway = PaymentGateway::with('currency')->find($validated['gateway_id']);

        if(!$payment_gateway){
            return Helpers::error(['error' => [__('Payment gateway not found').'!']]);
        }

        $trade = Trade::where('id', $trade_offer->trade_id)->first();
        if(!$trade){
            return Helpers::error(['error' => [__('Trade not found').'!']]);
        }

        $selling_currency = Currency::find($trade->currency_id);
        $currency = Currency::find($trade->rate_currency_id);
        $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->rate_currency_id)->first();

        if(!$wallet){
            return Helpers::error(['error' => [__('Your Wallet not found').'!']]);
        }

        $receiver_wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->currency_id)->first();


        $validated['trade_id']                    = $trade->id;
        $validated['saller_email']                = $trade->user->email;
        $validated['trade_creator_id']            = $trade->user_id;
        $validated['rate_currency']               = $trade->rateCurrency->code;
        $validated['receiver_wallet']             = $receiver_wallet;
        $validated['payment_gateway_id']          = $payment_gateway->id;
        $validated['payment_gateway_currency_id'] = $payment_gateway->currency->id;
        $validated['will_get']                    = $trade->amount;
        $validated['sale_currency']               = $trade->saleCurrency->code;
        $validated['amount']                      = $trade_offer->rate;
        $validated['subtotal']                    = $trade_offer->rate;
        $charges                                  = TransactionSetting::where('slug', 'trade')->first();
        $intervals                                = json_decode($charges->intervals);
        $charges                       = feesAndChargeCalculation($intervals, $validated['subtotal'],$currency->rate);
        $validated['wallet']           = $wallet;
        $validated['fixed_charge']     = $charges['fixed_charge'];
        $validated['percent_charge']   = $charges['percent_charge'];
        $validated['total_charge']     = $charges['total_charge'];
        $validated['total_amount']     = $validated['total_charge'] + $validated['subtotal'];
        $validated['transaction_type'] = PaymentGatewayConst::MARKETPLACE;


        if($wallet->balance < $validated['total_amount']){
            return Helpers::error(['error' => [__('Insufficient wallet balance').'!']]);
        }


        $payment_fields = $payment_gateway->input_fields ?? [];


        try {
            TemporaryData::where('user_id', Auth::guard(get_auth_guard())->user()->id)->where('type', PaymentGatewayConst::MARKETPLACE)->delete();
            $identifier = generate_unique_string("transactions","trx_id",16);

            TemporaryData::create([
                'user_id'       => Auth::id(),
                'type'          => PaymentGatewayConst::MARKETPLACE,
                'identifier'    => $identifier,
                'data'          => $validated,
            ]);

        } catch (Exception $e) {
            return Helpers::error(['success' => [__('Something went wrong. Please try again').'!']]);
        }

        $data = [
            'trx_id'         => $identifier,
            'payment_fields' => $payment_fields,
            'data'           => $validated,
        ];

        return Helpers::success($data,['success' => [__('Transaction insert successfully').'!']]);
    }


    public function evidenceSubmit(Request $request){

        $validator = Validator::make($request->all(), [
            'trx_id'           => 'required',
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        $identifier = TemporaryData::where('identifier', $validated['trx_id'])->where('type', PaymentGatewayConst::MARKETPLACE)->first();

        if(!$identifier){
            return Helpers::error(['error' => [__('Invalid request').'!']]);
        }

        $data = $identifier->data;

        $trade = Trade::find($data->trade_id);
        if(!$trade){
            return Helpers::error(['error' => [__('Invalid request').'!']]);
        }

        $receiver_wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->currency_id)->first();
        if(!$receiver_wallet){
            return Helpers::error(['error' => [__('Your wallet not found').'!']]);
        }

        $currency = Currency::find($trade->rate_currency_id);
        if(!$currency){
            return Helpers::error(['error' => [__('Currency not found').'!']]);
        }


        $payment_gateway = PaymentGateway::find($data->payment_gateway_id);
        $payment_fields = $payment_gateway->input_fields ?? [];
        $validation_rules       = $this->generateValidationRules($payment_fields);
        $payment_field_validate = Validator::make($request->all(), $validation_rules);
        if ($payment_field_validate->fails()) {
            $message =  ['error' => $payment_field_validate->errors()->all()];
            return Helpers::error($message);
        }
        $validated_api = $payment_field_validate->validate();
        $get_values = $this->placeValueWithFields($payment_fields, $validated_api);

        try {
            // sender record


            $trx_id = $this->trx_id;
            $transaction_id = $this->insertRecordSender($data, $trade->id,$trx_id,$get_values);
            $this->insertChargesSender($data,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceSender($transaction_id);
            $this->userWalletUpdate($data->total_amount, $data->wallet);
            $this->removeTempDataManual($validated['trx_id']);


            // seller profit
            $selling_wallet = UserWallet::where('currency_id', $trade->rate_currency_id)->where('user_id',$trade->user_id)->first();
            if(!$selling_wallet){
                return Helpers::error(['error' => [__('Your wallet not found').'!']]);
            }

            $wallet = UserWallet::where('currency_id', $trade->currency_id)->where('user_id',Auth::id())->first();

            Trade::find($trade->id)->update([
                'status' => PaymentGatewayConst::TRADE_STATUSPENDING,
            ]);
            $notification_content = [
                'title'         => __("Your Trade was purchased"),
                'message'       => "Your trade was purchased by @".auth()->user()->username." Please wait for the admin's response. Purchase Amount "."". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($data->amount,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::MARKETPLACE_TRANSACTION_ADDED,
                'user_id'  =>  Auth::guard(get_auth_guard())->user()->id,
                'message'   => $notification_content,
            ]);


            $notification_data = [
                'sender_currency'   => $data->rate_currency,
                'receiver_currency' => $data->sale_currency,
                'sender_amount'     => $data->amount,
                'receiver_amount'   => $data->will_get,
                'total_amount'      => $data->total_amount,
                'total_charge'      => $data->total_charge,
                'exchange_rate'     => $data->amount / $data->will_get,
                'sPrecision'        => get_wallet_precision($selling_wallet->currency),
                'rPrecision'        => get_wallet_precision($wallet->currency),
            ];


            // Mail send
            $user = auth()->user();
            $basic_settings = BasicSettingsProvider::get();
            try {
                if($basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'            => $trx_id,
                        'title'             => __("Your trade purchase request has been sent to the admin successfully. Please wait for the admin's response"),
                        'request_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'payable'           => get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'charges'           => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'received_amount'   => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'status'            => __("Pending"),
                    ];
                    $user->notify(new MarketplaceMailSender($user,(object)$notifyDataSender));
                }
            } catch (Exception $e) {
            }


            //push notification
            if($this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {
                }
            }




            $basic_setting = BasicSettings::first();


            //sms notification
            try{
                //sender sms
                if($basic_setting->sms_notification == true){
                    sendSms($user,'MARKETPLACE-SENDER',[
                        'request_amount'  => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'received_amount' => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'charge'          => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'from_user'       => $user->username.' ( '.$user->email ?? $user->full_mobile .' )',
                        'trx'             => $trx_id,
                        'time'            => now()->format('Y-m-d h:i:s A'),
                        // 'balance'         => get_amount($buyer_available_balance->balance,$buyer_available_balance->currency->code,$notification_data['rPrecision']),
                    ]);
                }
            }catch(Exception $e){}


            // receiver record
            $transaction_id = $this->insertRecordReceiver($data, $trade->id,$trx_id,1, $get_values);
            $this->insertChargesReceiver($data,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceReceiver($transaction_id);

            $notification_content = [
                'title'         => __("Your Trade was purchased"),
                'message'       => "Your trade was purchased by @".auth()->user()->username." Please wait for the admin's response. Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($data->amount,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::MARKETPLACE_TRANSACTION_ADDED,
                'user_id'  =>  $trade->user->id,
                'message'   => $notification_content,
            ]);

            // Mail send
            $user = $trade->user;
            $basic_settings = BasicSettingsProvider::get();
            try {
                if($basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'            => $trx_id,
                        'title'             => __("Your trade was purchased by @".auth()->user()->username." Please wait for the admin's response"),
                        'selling_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'     => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'status'            => __("Pending"),
                    ];
                    $user->notify(new MarketplaceMailReceiver($user,(object)$notifyDataSender));
                }
            } catch (Exception $th) {
            }

             //push notification
             if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {
                }
            }

            $basic_setting = BasicSettings::first();
            //sms notification
            try{
                //sender sms
                if($basic_setting->sms_notification == true){
                    sendSms($user,'MARKETPLACE-RECEIVER',[
                        'selling_amount' => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'  => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'from_user'      => auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'trx'            => $trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}


            //admin notification
            $user = userGuard()['user'];
            $this->adminNotification($trx_id,$notification_data,$user);

        } catch (Exception $e) {
            return Helpers::error(['error' => [__('Something went wrong. Please try again').'!']]);

        }

        return Helpers::onlySuccess(['success' => [__('Transaction create successfully.  Please wait for the admin\'s response').'!']]);
    }


     //admin notification
     public function adminNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade Request Successfully"),
            'greeting' =>__("Trade Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("Purchase By").": @".$user->email."<br>".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Fees & Charges")." : ".get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Total Payable Amount")." : ".get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__('Exchange Rate')." : ".get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Status")." : ".__("Pending"),

            //push notification
            'push_title' => __("User Transaction Create")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id.", ".__("Email").": @".$user->email.", ".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])." ".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),

            //admin db notification
            'notification_type' =>  NotificationConst::TRADE,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "User Transaction Create"." ".'Successful'." ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'])." (".$trx_id.")",
            'admin_db_message' =>"Email".": @".$user->email." "."Selling Amount"." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']).","."Asking Amount"." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])
        ];


        try{
            //notification
            (new NotificationHelper())->admin(['admin.trade.index','admin.trade.pending','admin.trade.ongoing','admin.trade.complete','admin.trade.canceled','admin.trade.approved','admin.trade.rejected'])
                                    ->mail(ActivityNotification::class, [
                                            'subject'   => $notification_content['subject'],
                                            'greeting'  => $notification_content['greeting'],
                                            'content'   => $notification_content['email_content'],
                                    ])
                                    ->push([
                                            'user_type' => "admin",
                                            'title' => $notification_content['push_title'],
                                            'desc'  => $notification_content['push_content'],
                                    ])
                                    ->adminDbContent([
                                        'type' => $notification_content['notification_type'],
                                        'title' => $notification_content['admin_db_title'],
                                        'message'  => $notification_content['admin_db_message'],
                                    ])
                                    ->send();


        }catch(Exception $e) {}

    }





}
