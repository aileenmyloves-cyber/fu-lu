<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Trade;
use App\Models\UserWallet;
use App\Models\Transaction;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
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


    public function index()
    {
        $page_title = ("Trade");
        $transactions = Transaction::with('trade')->orderBy('id', 'desc')->where('type', PaymentGatewayConst::TRADE)->where('user_id', Auth::user()->id)->paginate(12);
        return view('user.sections.trade.index',compact('page_title','transactions'));
    }


    public function create()
    {
        $page_title = ("Trade Create");
        $currency = Currency::active()->orderBy('default', 'asc')->get();
        $user_wallets = UserWallet::where('user_id', auth()->user()->id)->whereHas('currency',function($q){
            $q->where('status',GlobalConst::ACTIVE);
        })->with('currency:id,code,name,flag,rate,type,symbol')->get();

        $charges = TransactionSetting::where('slug', slug(PaymentGatewayConst::TRADE))->first();
        $intervals = $charges->intervals;

        return view('user.sections.trade.create',compact('page_title','user_wallets','currency','charges','intervals'));
    }


    // ajax call for get user available balance by currency
    public function availableBalanceByCurrency(Request $request){
        $user_wallets = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $request->id])->first();
        return $user_wallets->balance;
    }

      /**
     * My trade submit
     *
     * @method POST
     * @return Illuminate\Http\Response
     */
    public function submit(Request $request){
        $validator = Validator::make($request->all(), [
            'currency'           => 'required',
            'rate_currency'      => 'required',
            'amount'             => 'required|gt:0',
            'rate'               => 'required|gt:0',
            'pin'                =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
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

        $validated['transaction_type'] = PaymentGatewayConst::TRADE;

        $transactionSetting = TransactionSetting::where('slug', slug(PaymentGatewayConst::TRADE))->first();
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
        $rate_currency = Currency::find($validated['rate_currency']);
        if(!$rate_currency) return back()->with(['error' => [__('Receiver Currency Not Found')]]);

        $validated['subtotal'] = $validated['amount'];

        if($min_limit > $validated['subtotal'] || $max_limit < $validated['subtotal']){
            return back()->with(['error' => [__('Please follow the transaction limit').'!']]);
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
            return back()->with(['error' => [__('Your Wallet not found').'!']]);
        }

        if($wallet->balance < $validated['total_amount']){
            return back()->with(['error' => [__('Insufficient wallet balance').'!']]);
        }
        $validated['wallet'] = $wallet;

        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::TRADE,$wallet->currency,$validated['amount'],$transactionSetting,PaymentGatewayConst::SEND);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again."))]]);
        }

        try {
            $trx_id = generateTrxString("transactions","trx_id","MT",8);
            $trade_id = $this->tradeInsert((object) $validated);
            $transaction_id = $this->insertTradeTransaction((object) $validated,$trade_id,$trx_id);
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
                        'title'             => __("Trade Created to")." @" . $wallet->user->email ?? $wallet->user->full_mobile,
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

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with(['error' => [__('Something went wrong. Please try again').'!']]);
        }

        return redirect()->route('user.trade.complete', $trade_id)->with(['success' => [__('Trade Create Successfully')]]);

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
            'push_content' => __('web_trx_id')." ".$trx_id.", ".__("Email").": @".$user->email.", ".__("Selling Amount")." : ".get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision'])." ".__("Asking Amount")." : ".get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),

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


        }catch(Exception $e) {}

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


    public function transactionComplete($id){
        $url = route('user.marketplace.view', $id);
        $qrcode = generateQr($url);
        $page_title = __("Transaction Complete");
        return view('user.sections.trade.trade-preview',compact("page_title", 'qrcode', 'url'));
    }


    public function insertTradeTransaction($data,$trade_id,$trx_id) {

        try{

            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => Auth::user()->id,
                'trade_id'                    => $trade_id,
                'payment_gateway_currency_id' => $data->payment_gateway_currency_id ?? NULL,
                'user_wallet_id'              => $data->wallet->id,
                'type'                        => PaymentGatewayConst::TRADE,
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


    public function editTrade($id){
        $trade = Trade::with('saleCurrency','rateCurrency')->find($id);
        if(!$trade){
            return back()->with(['error' => [__('Invalid request').'!']]);
        }
        $page_title = __("Edit Trade");
        $currency = Currency::active()->orderBy('default', 'asc')->get();
        $wallet = UserWallet::where('currency_id', $trade->currency_id)->first();
        $default_currency = Currency::default();
        $charges = TransactionSetting::where('slug', slug(PaymentGatewayConst::TRADE))->first();
        $intervals = $charges->intervals;
        return view('user.sections.trade.edit', compact('page_title','trade','charges', 'currency','default_currency', 'intervals','wallet'));
    }


      /**
     * My trade edit
     *
     * @method POST
     * @return Illuminate\Http\Response
     */
    public function updateTrade(Request $request){
        $validator = Validator::make($request->all(), [
            'rate'      => 'required|gt:0',
            'target'    => 'required',
            'pin'       =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ]);

        if($validator->fails()){
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validate();
        $user = userGuard()['user'];

        //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false){
                if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
            }
        }
        $trade = Trade::findOrFail($validated['target']);
        $trade->update([
            'rate'       => $request->rate,
            'updated_at' => Carbon::now(),
        ]);


        $rate_currency = Currency::where('id',$trade->rate_currency_id)->first();
        if(!$rate_currency) return back()->with(['error' => [__('Receiver Currency Not Found')]]);

        $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $trade->currency_id)->first();
        if(!$wallet){
            return back()->with(['error' => [__('Your Wallet not found').'!']]);
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



        //push notification
        if( $this->basic_settings->push_notification == true){
            try{
                (new PushNotificationHelper())->prepare([$user->id],[
                    'title' => $notification_content['title'],
                    'desc'  => $notification_content['message'],
                    'user_type' => 'user',
                ])->send();
            }catch(Exception $e) {}
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
            if($basic_setting->email_notification == true){

                $notifyDataSender = [
                    'trx_id'            => $trade->transaction->trx_id,
                    'title'             => __("Trade Updated"),
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
                    'charge'         => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                    'from_user'      => $user->username.' ( '.$user->email ?? $user->full_mobile .' )',
                    'trx'            => $trade->transaction->trx_id,
                    'time'           => now()->format('Y-m-d h:i:s A'),
                    'balance'        => get_amount($wallet->balance,$wallet->currency->code,$notification_data['sPrecision']),
                ]);
            }
        }catch(Exception $e){}

        //admin notification
        $this->adminUpdateNotification($trade->transaction->trx_id,$notification_data,$user);

        return redirect()->route('user.trade.index')->with(['success' => [__('Trade updated successfully').'!']]);
    }


    public function adminUpdateNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade"),
            'greeting' =>__("Trade Updated"),
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


        }catch(Exception $e) {}

    }




    public function cancelTrade(Request $request){
        $transaction = Transaction::with('trade')->find($request->target);
        if(!$transaction){
            return back()->with(['error' => [__('Invalid request').'!']]);
        }

       try {
            $transaction->update([
                'status' => 7,
            ]);
            $transaction->trade->update([
                'status' => 7,
            ]);
       } catch (Exception $th) {
        return back()->with(['error' => [__('Something went wrong. Please try again').'!']]);
       }

       $rate_currency = Currency::where('id',$transaction->trade->rate_currency_id)->first();
       if(!$rate_currency) return back()->with(['error' => [__('Receiver Currency Not Found')]]);

       $wallet = UserWallet::where('user_id', Auth::id())->where('currency_id', $transaction->trade->currency_id)->first();
       if(!$wallet){
           return back()->with(['error' => [__('Your Wallet not found').'!']]);
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

    //admin notification
    $this->adminCancelNotification($transaction->trx_id,$notification_data,$user);

       return back()->with(['success' => [__('Trade close request send to admin successfully, Please wait for admin response').'!']]);
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


        }catch(Exception $e) {}

    }


}
