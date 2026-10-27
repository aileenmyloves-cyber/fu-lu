<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\Trade;
use PHPUnit\TextUI\Help;
use App\Models\UserWallet;
use App\Models\Transaction;
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
use App\Traits\PaymentGateway\ManualCrowPayment;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\Trade\MarketplaceMail;
use App\Notifications\User\Trade\MarketplaceMailSender;
use App\Notifications\User\Trade\MarketplaceMailReceiver;


class MarketplaceController extends Controller
{
    use ControlDynamicInputFields, ManualCrowPayment;

    protected  $trx_id;
    protected $basic_settings;

    public function __construct()
    {
        $this->trx_id = 'MP'.getTrxNum();
        $this->basic_settings = BasicSettingsProvider::get();
    }

    public function index(Request $request){

        $query = Trade::with('saleCurrency', 'rateCurrency', 'user')
            ->whereHas('user', function ($q) {
                $q->where('status', 1);
            })
            ->where('status', 1);

        // Apply Filters Dynamically
        if ($request->filled('currency')) {
            $query->where('currency_id', $request->currency)->orWhere('rate_currency_id', $request->currency);
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->filled('min_rate')) {
            $query->where('rate', '>=', $request->min_rate);
        }
        if ($request->filled('max_rate')) {
            $query->where('rate', '<=', $request->max_rate);
        }

        if ($request->filled('order_by')) {
            if ($request->order_by == 2) {
                $query->orderBy('rate', 'desc');
            } elseif ($request->order_by == 3) {
                $query->orderBy('rate', 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $trads = $query->with('saleCurrency','rateCurrency','user')->whereHas('user', function($q){
            $q->where('status', 1);
        })->paginate(12)->through(function($item) {
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
            $user = [
                'id'             => $item->user->id,
                'image'          => $item->user->image,
                'name'           => $item->user->firstname.' '.$item->user->lastname,
                'email_verified' => $item->user->email_verified,
                'kyc_verified'   => $item->user->kyc_verified,
            ];
            return[
                'image_path'    => get_files_public_path('user-profile'),
                'default_image' => get_files_public_path('default'),
                "base_ur"       => url('/'),
                'id'            => $item->id,
                'exchange_rate' => "1 ". $item->saleCurrency->symbol ." = ".  get_amount($item->rate / $item->amount,null, get_wallet_precision($item->rateCurrency)).' '. $item->rateCurrency->symbol,
                'amount'        => get_amount($item->amount,null, get_wallet_precision($item->saleCurrency)),
                'rate'          => get_amount($item->rate,null, get_wallet_precision($item->rateCurrency)),
                'sale_currency' => $sale_currency,
                'rate_currency' => $rate_currency,
                'user'          => $user,
            ];
        });

        $data = [
            'trads'      => $trads,
        ];

        return Helpers::success($data,['success' => [__('Marketplace data fetch successfully').'!']]);
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

        $trade = Trade::with('saleCurrency','rateCurrency')->where('id', $validated['target'])->get()->map(function ($item){
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
                'seller'        => get_amount($item->amount, null, get_wallet_precision($item->saleCurrency)),
                'subtotal'          => get_amount($item->rate, null, get_wallet_precision($item->rateCurrency)),
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

        // Charge calcualtion
        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);
        $charges = feesAndChargeCalculation($intervals, $trade->subtotal, $tradeRate->rateCurrency->rate);
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
     * My marketplace confirm
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

        $trade = Trade::find($validated['target']);
        if(!$trade){
            return Helpers::error(['error' => [__('Invalid request').'!']]);
        }
        $currency = Currency::find($trade->rate_currency_id);
        if(!$currency){
            return Helpers::error(['error' => [__('Currency not found').'!']]);
        }
        $wallet = UserWallet::where('user_id', Auth::guard(get_auth_guard())->user()->id)->where('currency_id', $trade->rate_currency_id)->first();
        if(!$wallet){
            return Helpers::error(['error' => [__('Your wallet not found').'!']]);
        }

        $validated['saller_email'] = $trade->user->email;
        $validated['trade_id']              = $trade->id;
        $validated['trade_creator_id'] = $trade->user_id;
        $validated['rate_currency']      = $trade->rateCurrency->code;
        $validated['payment_gateway_id'] = $payment_gateway->id;
        $validated['payment_gateway_currency_id'] = $payment_gateway->currency->id;
        $validated['will_get']           = getDynamicAmount($trade->amount);
        $validated['sale_currency']      = $trade->saleCurrency->code;
        $validated['amount']             = getDynamicAmount($trade->rate);
        $validated['subtotal']           = getDynamicAmount($trade->rate);
        $charges                         = TransactionSetting::where('slug', 'trade')->first();
        $intervals                       = json_decode($charges->intervals);
        $charges                         = feesAndChargeCalculation($intervals, $validated['subtotal'], $currency->rate);
        $validated['wallet']             = $wallet;
        $validated['fixed_charge']       = $charges['fixed_charge'];
        $validated['percent_charge']     = $charges['percent_charge'];
        $validated['total_charge']       = $charges['total_charge'];
        $validated['total_amount']       = $validated['total_charge'] + $validated['subtotal'];
        $validated['transaction_type']   = PaymentGatewayConst::MARKETPLACE;

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
            $transaction_id = $this->insertRecordSender($data,$trade->id ,$trx_id, $get_values);
            $this->insertChargesSender($data,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceSender($transaction_id);
            $this->userWalletUpdate($data->total_amount, $data->wallet);

            $wallet = UserWallet::where('currency_id', $trade->currency_id)->where('user_id',Auth::id())->first();


            Trade::find($trade->id)->update([
                'status' => PaymentGatewayConst::TRADE_STATUSPENDING,
            ]);
            $notification_content = [
                'title'         => __("Trade purchase request"),
                'message'       => "Your trade purchase request has been sent to the admin successfully. Please wait for the admin's response, Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." ".$trade->saleCurrency->code." Rate Amount"." ". get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::MARKETPLACE_TRANSACTION_ADDED,
                'user_id'  =>  Auth::guard(get_auth_guard())->user()->id,
                'message'   => $notification_content,
            ]);



            $notification_data = [
                'sender_currency'   => $trade->rateCurrency->code,
                'receiver_currency' => $trade->saleCurrency->code,
                'sender_amount'     => $trade->rate,
                'receiver_amount'   => $trade->amount,
                'total_charge'      => $data->total_charge,
                'total_amount'      => $trade->rate+$data->total_charge,
                'exchange_rate'     => $trade->rate / $trade->amount,
                'sPrecision'        => get_wallet_precision($wallet->currency),
                'rPrecision'        => get_wallet_precision($currency),
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
            } catch (\Throwable $th) {
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
                    ]);
                }
            }catch(Exception $e){}



             // receiver record
             $transaction_id = $this->insertRecordReceiver($data, $trade->id,$trx_id, $get_values);
             $this->insertChargesReceiver($data,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
             $this->insertDeviceReceiver($transaction_id);

             $notification_content = [
                'title'         => __("Your trade was purchased"),
                 'message'       => "Your trade was purchased by @".auth()->user()->username." Please wait for the admin's response.Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." ".$trade->saleCurrency->code." Rate Amount"." ". get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
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
             } catch (\Throwable $th) {
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
                 //receiver sms
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

        return Helpers::onlySuccess(['success' => [__('Transaction create successfully').'!']]);
    }



     //admin notification
     public function adminNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade Information"),
            'greeting' =>__("Trade Purchase Request Successfully"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("Purchase By").": @".$user->email."<br>".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Fees & Charges")." : ".get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Total Payable Amount")." : ".get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__('Exchange Rate')." : ".get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Trade Purchase Request")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id.", ".__("Email").": @".$user->email.", ".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])." ".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),

            //admin db notification
            'notification_type' =>  NotificationConst::TRADE,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Trade Purchase Request"." ".'Successful'." ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'])." (".$trx_id.")",
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