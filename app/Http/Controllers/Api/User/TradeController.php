<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\Trade;
use App\Models\UserWallet;
use App\Models\Transaction;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Http\Helpers\Api\Helpers;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\TransactionLimit;
use App\Http\Helpers\NotificationHelper;
use App\Models\Admin\TransactionSetting;
use Illuminate\Support\Facades\Validator;
use App\Notifications\User\Trade\TradeMail;
use App\Http\Helpers\PushNotificationHelper;
use App\Providers\Admin\BasicSettingsProvider;
use App\Notifications\Admin\ActivityNotification;


class TradeController extends Controller
{

    protected  $trx_id;
    protected $basic_settings;

    public function __construct()
    {
        $this->trx_id = 'SM'.getTrxNum();
        $this->basic_settings = BasicSettingsProvider::get();
    }


    public function currencyList(){
        $rate_currency = Currency::active()->orderBy('default', 'asc')->get()->map(function($item){
            return [
                'id'     => $item->id,
                'code'   => $item->code,
                'type'   => $item->type,
                'symbol' => $item->symbol,
                'flag'   => $item->flag ?? '',
                'rate'   => getDynamicAmount($item->rate),
            ];
        });


        $sale_currency = Currency::where('status', 1)->orderBy('default', 'asc')->get()->map(function($item){
            return [
                'id'     => $item->id,
                'code'   => $item->code,
                'type'   => $item->type,
                'symbol' => $item->symbol,
                'flag'   => $item->flag ?? '',
                'rate'   => getDynamicAmount($item->rate),
            ];
        });


        $wallet = UserWallet::with('currency')->where('user_id', Auth::id())->get()->map(function ($item){
            return [
                'id' => $item->id,
                'flag' => $item->currency->flag,
                'code' => $item->currency->code,
                'type'   => $item->currency->type,
                'name' => $item->currency->name,
                'balance' => getDynamicAmount($item->balance),
            ];
        });

        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        if(isset($intervals)){
            $first_element = current($intervals);
            $last_element = end($intervals);
            $min_limit = $first_element->min_limit;
            $max_limit = $last_element->max_limit;
        }else{
            $min_limit = '0';
            $max_limit = '0';
        }

        $data = [
            'base_url'      => get_asset_url('/'),
            'default_image' => files_asset_path_basename('default'),
            'image_path'    => files_asset_path_basename('currency-flag'),
            'rate_currency' => $rate_currency,
            'sale_currency' => $sale_currency,
            'wallet'        => $wallet,
            'trade_info'    => ['min_limit' => $min_limit, 'max_limit' => $max_limit]
        ];


        return Helpers::success(['success' => [__('Data Fetch Successfully')]], $data);
    }

    public function index(){
        // transaction
        $transactions = Transaction::with('trade')->orderBy('id', 'desc')->auth()->tradeTransaction()->get()->map(function ($item){
            $statusInfo = [
                "Ongoing"         => 1,
                "Pending"         => 2,
                "Cancelled"       => 4,
                "Payment Pending" => 5,
                "Sold"            => 6,
                "close Requested" => 7,
                "Closed"          => 8,
            ];

            return [
                'id'               => $item->id,
                'trade_id'         => $item->trade->id ??'',
                'trx'              => $item->trx_id,
                'transactin_type'  => $item->type,
                'request_amount'   => get_amount($item->request_amount,null, get_wallet_precision()),
                'payable'          => get_amount($item->payable,null, get_wallet_precision()),
                'total_charge'     => get_amount($item->charge->total_charge ?? '0',null, get_wallet_precision()),
                'buyer_will_pay'   => get_amount($item->trade->rate ?? '0',null, get_wallet_precision()),
                'buyer_will_get'   => get_amount($item->trade->amount ?? '0',null, get_wallet_precision()),
                'sale_currency'    => $item->trade->saleCurrency->code,
                'rate_currency'    => $item->trade->rateCurrency->code,
                'status'           => $item->tradeStringStatus->value,
                'status_id'        => $item->trade->status,
                'status_info'      => (object)$statusInfo,
                'rejection_reason' => $item->reject_reason ?? "",
                'created_at'        => $item->created_at ?? "",
            ];
        });


        $rate_currency = Currency::active()->orderBy('default', 'asc')->get()->map(function($item){
            return [
                'id'     => $item->id,
                'code'   => $item->code,
                'type'   => $item->type,
                'symbol' => $item->symbol,
                'flag'   => $item->flag ?? '',
                'rate'   => getDynamicAmount($item->rate),
            ];
        });

        // currency list
        $sale_currency = Currency::where('status', 1)->orderBy('default', 'asc')->get()->map(function($item){
            return [
                'id'     => $item->id,
                'code'   => $item->code,
                'type'   => $item->type,
                'symbol' => $item->symbol,
                'flag'   => $item->flag ?? '',
                'rate'   => getDynamicAmount($item->rate),
            ];
        });


        $wallet = UserWallet::with('currency')->where('user_id', Auth::id())->get()->map(function ($item){
            return [
                'id' => $item->id,
                'flag' => $item->currency->flag,
                'code' => $item->currency->code,
                'type' =>$item->currency->type,
                'name' => $item->currency->name,
                'balance' => getDynamicAmount($item->balance),
            ];
        });

        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        if(isset($intervals)){
            $first_element = current($intervals);
            $last_element = end($intervals);
            $min_limit = $first_element->min_limit;
            $max_limit = $last_element->max_limit;
        }else{
            $min_limit = '0';
            $max_limit = '0';
        }

        $tradeCharges = TransactionSetting::where('slug','trade')->where('status',1)->get()->map(function($data){
            return[
                'id'                        => $data->id,
                'slug'                      => $data->slug,
                'title'                     => $data->title,
                'intervals'               => json_decode($data->intervals),
                'monthly_limit'             => get_amount($data->monthly_limit,null,get_wallet_precision()),
                'daily_limit'               => get_amount($data->daily_limit,null,get_wallet_precision()),
            ];
        })->first();


        $data = [
            'base_url'      =>get_asset_url('/'),
            'default_image' => files_asset_path_basename('default'),
            'image_path'    => files_asset_path_basename('currency-flag'),
            'trade'    => $transactions,
            'rate_currency' => $rate_currency,
            'sale_currency' => $sale_currency,
            'wallet'        => $wallet,
            'trade_Charge'  => $tradeCharges,
        ];

        $message = ['success' => [__('Trade data fetch successfully').'!']];
        return Helpers::success($data, $message);
    }



    public function submit(Request $request){
        $validator = Validator::make($request->all(), [
            'currency'           => 'required',
            'rate_currency'      => 'required',
            'amount'             => 'required|gt:0',
            'rate'               => 'required|gt:0',
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        $validated['transaction_type'] = PaymentGatewayConst::TRADE;

        $transactionSetting = TransactionSetting::where('slug', PaymentGatewayConst::TRADE)->first();
        if(!$transactionSetting){
            $message =  ['error'=>[__('Transaction Setting Not Found')]];
            return Helpers::error($message);
        }
        $intervals = json_decode($transactionSetting->intervals);

        if(isset($intervals)){
            $first_element = current($intervals);
            $last_element = end($intervals);
            $min_limit = $first_element->min_limit;
            $max_limit = $last_element->max_limit;
        }else{
            $min_limit = 0;
            $max_limit = 0;
        }

        $currency = Currency::find($validated['currency']);
        if(!$currency){
            $message =  ['error'=>[__('Sender Currency Not Found')]];
            return Helpers::error($message);
        }
        $rate_currency = Currency::find($validated['rate_currency']);
        if(!$rate_currency){
            $message =  ['error'=>[__('Receiver Currency Not Found')]];
            return Helpers::error($message);
        }

        $validated['subtotal'] = $validated['amount'];

        if($min_limit > $validated['subtotal'] || $max_limit < $validated['subtotal']){
            $message =  ['error'=>[__('Please follow the transaction limit')]];
            return Helpers::error($message);
        }

        $charges = feesAndChargeCalculation($intervals, $validated['subtotal'],$currency->rate);

        $validated['rate_currency_code'] = $rate_currency->code;
        $validated['sale_currency_code'] = $currency->code;
        $validated['fixed_charge']   = $charges['fixed_charge'];
        $validated['percent_charge'] = $charges['percent_charge'];
        $validated['total_charge']   = $charges['total_charge'];
        $validated['total_amount'] = $validated['total_charge'] + $validated['subtotal'];


        $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $validated['currency'])->first();
        if(!$wallet){
            $message =  ['error'=>[__('Your Wallet not found')]];
            return Helpers::error($message);
        }

        $rate_currency = Currency::find($validated['rate_currency']);
        if(!$rate_currency) return Helpers::error(['error' => [__('Receiver Currency Not Found')]]);

        if($wallet->balance < $validated['total_amount']){
            $message =  ['error'=>[__('Insufficient wallet balance').'!']];
            return Helpers::error($message);
        }
        $validated['wallet'] = $wallet;

        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::TRADE,$wallet->currency,$validated['amount'],$transactionSetting,PaymentGatewayConst::SEND);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            $error = ['error'=>[__($errorData['message'] ?? __("Something went wrong! Please try again."))]];
            return Helpers::error($error);
        }



        try {

            $trx_id = generateTrxString("transactions","trx_id","MT",8);
            $id = $this->tradeInsert((object) $validated);
            $transaction_id = $this->insertTradeTransaction((object) $validated,$id,$trx_id);
            $this->insertTradeCharges((object) $validated,$transaction_id);
            $this->insertTradeTrxDevice($transaction_id);
            $this->userWalletUpdate($validated['total_amount'], $wallet);

            $notification_content = [
                'title'         => "Trade Created",
                'message'       => "Selling Amount ". get_amount($validated['amount'],$currency->code,get_wallet_precision($currency))." Asking Amount"." ". get_amount($validated['rate'],$rate_currency->code,get_wallet_precision($rate_currency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::TRADE,
                'user_id'  =>  Auth::guard(get_auth_guard())->user()->id,
                'message'   => $notification_content,
            ]);

            $user = userGuard()['user'];

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


            $notification_data = [
                'sender_currency'   => $currency->code,
                'receiver_currency' => $rate_currency->code,
                'sender_amount'     => $validated['amount'],
                'receiver_amount'   => $validated['rate'],
                'total_amount'      => $validated['total_amount'],
                'total_charge'      => $validated['total_charge'],
                'exchange_rate'     => $validated['rate'] / $validated['amount'],
                'sPrecision'        => get_wallet_precision($wallet->currency),
                'rPrecision'        => get_wallet_precision($rate_currency),
            ];

            $basic_setting = BasicSettings::first();
            $user = userGuard()['user'];
               try{
                   if( $basic_setting->email_notification == true){

                       $notifyDataSender = [
                           'trx_id'            => $trx_id,
                           'title'             => __("Trade Created to")." @" . @$wallet->user->username." (".@$wallet->user->email ?? @$wallet->user->full_mobile.")",
                           'request_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                           'payable'           => get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                           'charges'           => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                           'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                           'received_amount'   => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                           'status'            => __("success"),
                       ];
                       //sender notifications
                       $user->notify(new TradeMail($user,(object)$notifyDataSender));

                   }
                }catch(Exception $e){}

                //sms notification
                try{
                   //sender sms
                   if($basic_setting->sms_notification == true){
                        sendSms($user,'TRADE',[
                            'selling_amount' => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                            'asking_amount'  => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                            'charge'         => get_amount( $notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                            'from_user'      => $user->username.' ( '.$user->email ?? $user->full_mobile .' )',
                            'trx'            => $trx_id,
                            'time'           => now()->format('Y-m-d h:i:s A'),
                            'balance'        => get_amount($wallet->balance,$wallet->currency->code,$notification_data['sPrecision']),
                        ]);
                    }
                }catch(Exception $e){}

            //admin notification
            $this->adminNotification($trx_id,$notification_data,$user);

        } catch (Exception $e) {
            $message =  ['error' => [__('Something went wrong. Please try again').'!']];
            return Helpers::error($message);
        }



        $url = route('user.marketplace.view', $id);
        $qrcode = generateQr($url);


        $preview = [
            'message' => __('Trade created successfully'),
            'details' => __('Your post will be approved once your funds have been received. You can share this URL or QR code with a potential buyer if you already have one!'),
            'qrcode' => $qrcode,
            'id'     => $id,
        ];

        $data = [
            'preview' => $preview,
            'transaction' => $notification_data,
        ];

        $message =  ['success'=>[__('Trade insert successfully')]];
        return Helpers::success($data,$message);

    }



     //admin notification
     public function adminNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade"),
            'greeting' =>__("Trade Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("Email").": @".$user->email."<br>".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Fees & Charges")." : ".get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Total Payable Amount")." : ".get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__('Exchange Rate')." : ".get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Trade Create")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id." ".__("Email").": @".$user->email." ".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])." ".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),

            //admin db notification
            'notification_type' =>  NotificationConst::TRADE,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Trade Create"." ".'Successful'." ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'])." (".$trx_id.")",
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


        }catch(Exception $e) {
        }

    }



    public function tradeInsert($data){
        try{
            $id = DB::table("trades")->insertGetId([
                'user_id'          => Auth::id(),
                'currency_id'      => $data->currency,
                'amount'           => $data->amount,
                'rate'             => $data->rate,
                'rate_currency_id' => $data->rate_currency,
                'status'           => 1,
                'created_at'       => now(),
            ]);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $id;
    }



    public function insertTradeTransaction($data,$trade_id = null, $trx_id) {

        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => Auth::user()->id,
                'trade_id'                    => $trade_id,
                'payment_gateway_currency_id' => $data->payment_gateway_currency_id ?? NULL,
                'user_wallet_id'              => $data->wallet->id,
                'type'                        => $data->transaction_type,
                'trx_id'                      => $trx_id,
                'request_amount'              => $data->amount,
                'payable'                     => $data->subtotal,
                'available_balance'           => $data->wallet->balance - $data->total_amount,
                'remark'                      => ucwords(remove_speacial_char($data->transaction_type)),
                'details'                     => json_encode(PaymentGatewayConst::TRADE),
                'status'                      => PaymentGatewayConst::STATUSSUCCESS,
                'created_at'                  => now(),
            ]);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function insertTradeCharges($data,$id) {

        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $data->percent_charge,
                'fixed_charge'      => $data->fixed_charge,
                'total_charge'      => $data->total_charge,
                'created_at'        => now(),
            ]);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    public function insertTradeTrxDevice($id) {
        $client_ip = request()->ip() ?? false;
        $location  = geoip()->getLocation($client_ip);
        $agent     = new Agent();
        $mac       = "";
        try{
            DB::table("transaction_devices")->insert([
                'transaction_id'=> $id,
                'ip'            => $client_ip,
                'mac'           => $mac,
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    public function userWalletUpdate($amount, $wallet){
        $wallet = UserWallet::find($wallet->id);
        $wallet->update([
            'balance' => $wallet->balance - $amount,
        ]);
    }

    public function removeTempTradeData($identifier) {
        if(isset($identifier)){
             TemporaryData::where("identifier",$identifier)->delete();
        }
    }


    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target' => 'required|integer', // Added 'integer' validation for robustness
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }

        $trade = Trade::with('saleCurrency', 'rateCurrency')
            ->where('id', $request->target)
            ->first(); // Use `first()` instead of `get()->first()`

        if (!$trade) {
            return Helpers::error(['error' => [__('Invalid request') . '!']]);
        }

        $tradeData = [
            'id' => $trade->id,
            'amount' => get_amount($trade->amount,null,get_wallet_precision($trade->saleCurrency)),
            'rate' => get_amount($trade->rate,null,get_wallet_precision($trade->rateCurrency)),
            'sale_currency' => [
                'id' => $trade->saleCurrency->id,
                'code' => $trade->saleCurrency->code,
                'symbol' => $trade->saleCurrency->symbol,
                'flag' => $trade->saleCurrency->flag ?? '',
                'rate' => getDynamicAmount($trade->saleCurrency->rate),
            ],
            'rate_currency' => [
                'id' => $trade->rateCurrency->id,
                'code' => $trade->rateCurrency->code,
                'symbol' => $trade->rateCurrency->symbol,
                'flag' => $trade->rateCurrency->flag ?? '',
                'rate' => getDynamicAmount($trade->rateCurrency->rate),
            ],
        ];


        $message =  ['success'=>[__('Data fetched successfully')]];
        return Helpers::success($tradeData, $message);
    }





    /**
     * My trade edit
     *
     * @method POST
     * @return Illuminate\Http\Response
     */
    public function updateTrade(Request $request){
        $validator = Validator::make($request->all(), [
            'rate' => 'required|gt:0',
            'target' => 'required',
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validate();
        $trade = Trade::find($validated['target']);
        if(!$trade){
            $message =  ['error'=>[__('Invalid request')]];
            return Helpers::error($message);
        }
        $trade->update([
            'rate'       => $request->rate,
            'updated_at' => Carbon::now(),
        ]);

        $rate_currency = Currency::where('id',$trade->rate_currency_id)->first();
        if(!$rate_currency) return Helpers::error(['error' => [__('Receiver Currency Not Found')]]);

        $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->currency_id)->first();
        if(!$wallet){
            return Helpers::error(['error' => [__('Your Wallet not found').'!']]);
        }

        $notification_content = [
            'title'         => "Trade Updated",
            'message'       => "Selling Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Asking Amount"." ". get_amount($validated['rate'],$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
            'time'          => Carbon::now()->diffForHumans(),
            'image'         => files_asset_path('profile-default'),
        ];

        UserNotification::create([
            'type'      => NotificationConst::TRADE,
            'user_id'  =>  Auth::guard(get_auth_guard())->user()->id,
            'message'   => $notification_content,
        ]);

        $user = userGuard()['user'];

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



        $notification_data = [
            'sender_currency'   => $trade->saleCurrency->code,
            'receiver_currency' => $trade->rateCurrency->code,
            'sender_amount'     => $trade->amount,
            'receiver_amount'   => $validated['rate'],
            'total_charge'      => $trade->transaction->charge->total_charge,
            'total_amount'      => $trade->transaction->request_amount+$trade->transaction->charge->total_charge,
            'exchange_rate'     => $validated['rate'] / $trade->amount,
            'sPrecision'        => get_wallet_precision($wallet->currency),
            'rPrecision'        => get_wallet_precision($rate_currency),
        ];

        $basic_setting = BasicSettings::first();
        $user = userGuard()['user'];
        try{
            if( $basic_setting->email_notification == true){

                $notifyDataSender = [
                    'trx_id'            => $trade->transaction->trx_id,
                    'title'             => __("Trade Update to")." @" . @$wallet->user->username." (".@$wallet->user->email ?? @$wallet->user->full_mobile.")",
                    'request_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'payable'           => get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'charges'           => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                    'received_amount'   => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                    'status'            => __("success"),
                ];
                //sender notifications
                $user->notify(new TradeMail($user,(object)$notifyDataSender));

            }
        }catch(Exception $e){}

        //sms notification
        try{
            //sender sms
            if($basic_setting->sms_notification == true){
                sendSms($user,'TRADE',[
                    'selling_amount' => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'asking_amount'  => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                    'charge'         => get_amount( $notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'from_user'     => $user->username.' ( '.$user->email ?? $user->full_mobile .' )',
                    'trx'            => $trade->transaction->trx_id,
                    'time'           => now()->format('Y-m-d h:i:s A'),
                    'balance'        => get_amount($wallet->balance,$wallet->currency->code,$notification_data['sPrecision']),
                ]);
            }
        }catch(Exception $e){}

        //admin notification
        $this->adminUpdateNotification($trade->transaction->trx_id,$notification_data,$user);

        $message =  ['success'=>[__('Trade updated successfully')]];
        return Helpers::onlysuccess($message);

    }


    public function adminUpdateNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade"),
            'greeting' =>__("Trade Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("Email").": @".$user->email."<br>".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Fees & Charges")." : ".get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Total Payable Amount")." : ".get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__('Exchange Rate')." : ".get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Trade Updated")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id." ".__("Email").": @".$user->email." ".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])." ".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),

            //admin db notification
            'notification_type' =>  NotificationConst::TRADE,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Trade Updated"." ".'Successful'." ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'])." (".$trx_id.")",
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


        }catch(Exception $e) {
        }

    }



    public function cancelTrade(Request $request){
        $transaction = Transaction::with('trade')->find($request->target);
        if(!$transaction){
            $message =  ['error'=>[__('Invalid request')]];
            return Helpers::error($message);
        }

       try {
            $transaction->update([
                'status' => 7,
            ]);
            $transaction->trade->update([
                'status' => 7,
            ]);
       } catch (Exception $th) {
        $message =  ['error' => [__('Something went wrong. Please try again').'!']];
        return Helpers::error($message);
       }

       $rate_currency = Currency::where('id',$transaction->trade->rate_currency_id)->first();
       if(!$rate_currency) return Helpers::error(['error' => [__('Receiver Currency Not Found')]]);

       $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $transaction->trade->currency_id)->first();
       if(!$wallet){
           return Helpers::error(['error' => [__('Your Wallet not found').'!']]);
       }
       $user = userGuard()['user'];

    $notification_data = [
        'sender_currency'   => $transaction->trade->saleCurrency->code,
        'receiver_currency' => $transaction->trade->rateCurrency->code,
        'sender_amount'     => $transaction->trade->amount,
        'receiver_amount'   => $transaction->trade->rate,
        'total_charge'      => $transaction->charge->total_charge,
        'total_amount'      => $transaction->request_amount+$transaction->charge->total_charge,
        'exchange_rate'     => $transaction->trade->rate / $transaction->trade->amount,
        'sPrecision'        => get_wallet_precision($wallet->currency),
        'rPrecision'        => get_wallet_precision($rate_currency),
    ];



       $this->adminCancelNotification($transaction->trx_id,$notification_data,$user);

       $message =  ['success' => [__('Trade close request send to admin successfully, Please wait for admin response').'!']];
       return Helpers::onlysuccess($message);


    }


    public function adminCancelNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade"),
            'greeting' =>__("Trade Close Request"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("Email").": @".$user->email."<br>".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Fees & Charges")." : ".get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__("Total Payable Amount")." : ".get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])."<br>".__('Exchange Rate')." : ".get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision'])."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Trade Close Request")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id." ".__("Email").": @".$user->email." ".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])." ".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),

            //admin db notification
            'notification_type' =>  NotificationConst::TRADE,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Trade Close Request"." ".'Successful'." ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'])." (".$trx_id.")",
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


        }catch(Exception $e) {
        }

    }






}