<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\User;
use App\Models\Trade;
use App\Models\TradeOffer;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Http\Helpers\Api\Helpers;
use Illuminate\Support\Facades\DB;
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
use App\Notifications\User\Trade\TradeMail;
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

    public function index(){
        $page_title = __("Get Offer");
        $get_offers = TradeOffer::with('trades','saleCurrency','rateCurrency','creator','receiver')
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
        $default_currency = get_default_currency_code();
        return view('user.sections.trade-offer.index',compact("page_title", "get_offers", "default_currency"));
    }


    public function counterSubmit(Request $request){

        $validator = Validator::make($request->all(), [
            'rate'        => 'required',
            'trade_id'    => 'required',
            'type'        => 'required',
            'receiver_id' => 'nullable',
            'creator_id'  => 'nullable',
        ]);

        if($validator->fails()){
            return back()->withErrors($validator)->withInput()->with('modal','offerCounterModal');
        }

        $validated = $validator->safe()->all();

        $trade = Trade::where('id', $validated['trade_id'])->first();
        if(!$trade){
            return back()->with(['error' => [__('Trade not found')]]);
        }

        try {

            if($validated['type'] == 'COUNTER_OFFER'){
                if($validated['receiver_id'] == Auth::id()){
                    $receiver_id = $validated['creator_id'];
                }else{
                    $receiver_id = $validated['receiver_id'];
                }
            }else{
                $receiver_id = $trade->user_id;
                if($trade->user_id == Auth::id()){
                    return back()->with(['error' => [__('Can not send offer request for your trade')]]);
                }
            }


            TradeOffer::create([
                'type'             => $validated['type'],
                'trade_id'         => $trade->id,
                'creator_id'       => Auth::id(),
                'receiver_id'      => $receiver_id,
                'trade_user_id'    => $trade->user_id,
                'sale_currency_id' => $trade->currency_id,
                'amount'           => $trade->amount,
                'rate'             => $validated['rate'],
                'rate_currency_id' => $trade->rate_currency_id,
                'status'           => 2,
            ]);

            $user = User::where('id', $receiver_id)->first();
            if(!$user){
                return back()->with(['error' => [__('User not found')]]);
            }


            if($validated['type'] == 'COUNTER_OFFER'){
                $notification_content = [
                    'title'         => "Counter Offer",
                    'message'       => "Offer Price ".get_amount($trade->amount, $trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency->currency)).' @ '.get_amount($validated['rate'],$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency->currency)) . " Selling Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency->currency)).' @ '.get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency->currency)),
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
                    'message'       => "Offer Price ".get_amount($trade->amount, $trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency->currency)).' @ '.get_amount($validated['rate'],$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency->currency))." Selling Price ".get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency->currency)).' @ '.get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency->currency)),
                    'time'          => Carbon::now()->diffForHumans(),
                    'image'         => files_asset_path('profile-default'),
                ];

                UserNotification::create([
                    'type'      => NotificationConst::OFFER,
                    'user_id'  =>  $receiver_id,
                    'message'   => $notification_content,
                ]);
            }

            $basic_settings = BasicSettingsProvider::get();
            //push notification
            if($basic_settings->push_notification == true){
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
            if(!$rate_currency) return back()->with(['error' => [__('Receiver Currency Not Found')]]);


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
                        'title'             => $notification_content['title']." Form @" . auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
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
                        'from_user'     => $user->username.' ( '.$user->email ?? $user->full_mobile .' )',
                        'trx'            => $trade->transaction->trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}

        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        $success = ['success' => [__('Your offer sent successfully').'!']];

        return back()->with($success);
    }




    public function offerStatus(Request $request){
        $validated = Validator::make($request->all(), [
            'target' => 'required',
            'type' => 'required',
        ])->validated();

        $tradeOffer = TradeOffer::find($request->target);
        if(!$tradeOffer){
            return back()->with(['error' => [__('Trade offer not found').'!']]);
        }
        $trade = Trade::find($tradeOffer->trade_id);

        if($validated['type'] == 'Reject'){
            $status = 4;
            $title = __("Offer Rejected");
            $messageText = ['success'=> ['Trade offer reject successfully']];
        } elseif($validated['type'] == 'Accept') {
            $status = 1;
            $title = __("Offer Accepted");
            $messageText = ['success'=> ['Trade offer accept successfully']];
        }

        $receiver = $tradeOffer->creator;
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
         $basic_settings = BasicSettingsProvider::get();
         if($basic_settings->push_notification == true){
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
        if(!$rate_currency) return back()->with(['error' => [__('Receiver Currency Not Found')]]);

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
                return back()->with(['error' => __('Trade offer is not found')]);
            }


            $trade_offer->update([
                'status' => $status,
            ]);
            return back()->with($messageText);

        } catch (Exception $th) {
            return back()->with(['error' => __('Something went wrong. Please try again').'!']);
        }

    }


     /**
     * Buy Preview
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function preview($id){
        $page_title = __("Preview Trade");
        $trade_offer = TradeOffer::with('saleCurrency','rateCurrency')->findOrFail($id);

        if(!$trade_offer){
            return back()->with(['error' => [__('Transaction failed please try again').'!']]);
        }

        $currency = Currency::findOrFail($trade_offer->rate_currency_id);

        $user = Auth::user();
        $userWallet = UserWallet::with('currency')->where('user_id', Auth::id())->where('currency_id',$trade_offer->rate_currency_id)->first();

        // Charge calcualtion
        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        $charges = feesAndChargeCalculation($intervals, $trade_offer->rate,$currency->rate);
        $total_charge = $charges['fixed_charge'] + $charges['percent_charge'];

        $currency_code = $trade_offer->saleCurrency->code;

        $payment_gatewaies = PaymentGateway::with('currencies')
                                            ->where('type', PaymentGatewayConst::MANUAL)
                                            ->where('slug', PaymentGatewayConst::receiving_method_slug())
                                            ->whereHas('currencies', function($q) use ($currency_code) {
                                                $q->where('currency_code', $currency_code);
                                            })
                                            ->get();

        if(!isset($payment_gatewaies)){
            return back()->with(['error' => [__('Payment gateway not found').'!']]);
        }

        return view('user.sections.trade-offer.preview',compact("page_title", "user", "userWallet", "trade_offer", 'total_charge', 'payment_gatewaies'));
    }



       /**
     * My excrow submit
     *
     * @method POST
     * @return Illuminate\Http\Response
     */
    public function buyTrade(Request $request){
        $validator = Validator::make($request->all(),[
            'offer_id'          => 'required',
            'payment_gateway'   => 'required',
            'pin'               =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ]);
        if($validator->fails()){
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();

        $user = userGuard()['user'];

        //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false){
                if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
            }
        }

        $trade_offer = TradeOffer::where('id', $validated['offer_id'])->first();
        if(!$trade_offer){
            return back()->with(['error' => [__('Trade offer not found').'!']]);
        }


        return redirect()->route('user.trade.offer.evidence', [$validated['offer_id'], $validated['payment_gateway']]);

    }


    /**
     * My marketplace evidence page show
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function evidenceView($offer_id, $gateway_id){
        $page_title = __("Evidence");
        $trade_offer = TradeOffer::where('id', $offer_id)->first();
        if(!$trade_offer){
            return back()->with(['error' => [__('Trade Offer not found').'!']]);
        }
        $payment_gateway = PaymentGateway::findOrFail($gateway_id);

        return view('user.sections.trade-offer.avidence',compact("page_title", "trade_offer", "payment_gateway"));
    }




       /**
     * My marketplace evidence submit
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function evidenceSubmit(Request $request){
        $validator = Validator::make($request->all(),[
            'trade_offer_id' => 'required',
            'gateway_id'     => 'required',
        ]);

        if($validator->fails()){
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();
        $trade_offer = TradeOffer::where('id', $validated['trade_offer_id'])->first();
        if(!$trade_offer){
            return back()->with(['error' => [__('Trade offer not found').'!']]);
        }

        $payment_gateway = PaymentGateway::with('currency')->find($validated['gateway_id']);

        if(!$payment_gateway){
            return back()->with(['error' => [__('Payment gateway not found').'!']]);
        }

        $trade = Trade::where('id', $trade_offer->trade_id)->first();
        if(!$trade){
            return back()->with(['error' => [__('Trade not found').'!']]);
        }

        $selling_currency = Currency::find($trade->currency_id);
        $currency = Currency::find($trade->rate_currency_id);



        $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->rate_currency_id)->first();


        if(!$wallet){
            return back()->with(['error' => [__('Your Wallet not found').'!']]);
        }

        $receiver_wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->currency_id)->first();

        $validated['saller_email'] = $trade->user->email;
        $validated['trade_creator_id'] = $trade->user_id;
        $validated['rate_currency'] = $trade->rateCurrency->code;
        $validated['receiver_wallet'] = $receiver_wallet;
        $validated['payment_gateway_id'] = $payment_gateway->id;
        $validated['payment_gateway_currency_id'] = $payment_gateway->currency->id;
        $validated['will_get'] = $trade->amount;
        $validated['sale_currency'] = $trade->saleCurrency->code;
        $validated['amount'] = $trade_offer->rate;
        $validated['subtotal'] = $trade_offer->rate;
        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        $charges = feesAndChargeCalculation($intervals, $validated['subtotal'],$currency->rate);
        $validated['wallet'] = $wallet;
        $validated['fixed_charge']   = $charges['fixed_charge'];
        $validated['percent_charge'] = $charges['percent_charge'];
        $validated['total_charge']   = $charges['total_charge'];
        $validated['total_amount'] = $validated['total_charge'] + $validated['subtotal'];
        $validated['transaction_type'] = PaymentGatewayConst::MARKETPLACE;


        if($wallet->balance < $validated['total_charge']){
            return back()->with(['error' => [__('Insufficient wallet balance').'!']]);
        }

        $payment_fields = $payment_gateway->input_fields ?? [];

        $validation_rules       = $this->generateValidationRules($payment_fields);
        $payment_field_validate = Validator::make($request->all(),$validation_rules)->validate();
        $get_values             = $this->placeValueWithFields($payment_fields,$payment_field_validate);

        try {
            // sender record
            $trx_id = $this->trx_id;
            $transaction_id = $this->insertRecordSender((object) $validated, $trade->id,$trx_id,$get_values);
            $this->insertChargesSender((object) $validated,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceSender($transaction_id);
            $this->userWalletUpdate($validated['total_amount'], $wallet);

            // seller profit
            $selling_wallet = UserWallet::where('currency_id', $trade->rate_currency_id)->where('user_id',$trade->user_id)->first();

            // my profit
            $charges = feesAndChargeCalculation($intervals, $validated['subtotal'],$selling_currency->rate);
            $total = $validated['will_get'];
            $wallet = UserWallet::where('currency_id', $trade->currency_id)->where('user_id',Auth::id())->first();


            Trade::find($trade->id)->update([
                'status' => PaymentGatewayConst::TRADE_STATUSPENDING,
            ]);
            $notification_content = [
                'title'         => __("Trade purchase request"),
                'message'       => "Your trade purchase request has been sent to the admin successfully. Please wait for the admin's response.Purchase Amount "."". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($trade_offer->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::MARKETPLACE_TRANSACTION_ADDED,
                'user_id'  =>  Auth::guard(get_auth_guard())->user()->id,
                'message'   => $notification_content,
            ]);

            $notification_data = [
                'sender_currency'   => $validated['rate_currency'],
                'receiver_currency' => $validated['sale_currency'],
                'sender_amount'     => $validated['amount'],
                'receiver_amount'   => $validated['will_get'],
                'total_amount'      => $validated['total_amount'],
                'total_charge'      => $validated['total_charge'],
                'exchange_rate'     => $validated['amount'] / $validated['will_get'],
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
            if($basic_settings->push_notification == true){
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
            $user = userGuard()['user'];


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
            $transaction_id = $this->insertRecordReceiver((object) $validated, $trade->id,$trx_id, $get_values);
            $this->insertChargesReceiver((object) $validated,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceReceiver($transaction_id);

            $notification_content = [
                'title'         => __("Your Trade was purchased"),
                'message'       => "Your trade was purchased by @".auth()->user()->username." Please wait for the admin's response. Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($trade_offer->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
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
            } catch (Exception $e) {
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

            DB::commit();


        } catch (Exception $e) {
            DB::rollBack();
            return back()->with(['error' => [__('Something went wrong. Please try again').'!']]);
        }

        return redirect()->route('user.transactions.index')->with(['success' => [__('Transaction create successfully.  Please wait for the admin\'s response')]]);

    }


     //admin notification
     public function adminNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade Request Successfully"),
            'greeting' =>__("Trade Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("Purchase By").": @".$user->email."<br>".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Fees & Charges")." : ".get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Total Payable Amount")." : ".get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__('Exchange Rate')." : ".get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Status")." : ".__("success"),

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
