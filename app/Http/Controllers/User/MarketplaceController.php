<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\User;
use App\Models\Trade;
use App\Models\TradeOffer;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
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

        $page_title = __('Marketplace');
        $currencies = Currency::orderByDesc('default')->get();

        $query = Trade::with('saleCurrency', 'rateCurrency', 'user')->where('status', 1)
            ->whereHas('user', function ($q) {
                $q->where('status', 1);
            });

        // Apply Filters Dynamically
        if ($request->filled('currency')) {
            $query->where('currency_id', $request->currency)->orWhere('rate_currency_id', $request->currency)->where('status', 1);
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

        $query->where('status', 1);

        $trade = $query->paginate(12);

        return view('user.sections.marketplace.index',compact("page_title", "trade","currencies"));
    }



    public function viewTrade($id){
        $page_title = __("Marketplace View");
        $trade = Trade::with('saleCurrency','rateCurrency')->find($id);
        if(!$trade){
            return back()->with(['error' => [__('Transaction failed please try again!')]]);
        }
        $currencies = Currency::orderByDesc('default')->get();
        return view('user.sections.marketplace.view',compact("page_title", "trade","currencies"));
    }



       /**
     * My trade page show
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function preview($id){
        $page_title = __("Preview Trade");

        $trade = Trade::with('saleCurrency','rateCurrency')->findOrFail($id);

        if(!$trade){
            return back()->with(['error' => [__('Transaction failed please try again!')]]);
        }

        if($trade->user_id == Auth::id()){
            return back()->with(['error' => [__('Can not buy your Trade!')]]);
        }

        $currency = Currency::findOrFail($trade->rate_currency_id);
        $userWallet = UserWallet::with('currency')->where('user_id', Auth::id())->where('currency_id',$trade->rate_currency_id)->first();
        // Charge calcualtion
        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        $charges =  feesAndChargeCalculation($intervals, $trade->rate,$currency->rate);
        $total_charge = $charges['total_charge'];

        $currency_code = $trade->saleCurrency->code;

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


        return view('user.sections.marketplace.preview',compact("page_title", "trade", 'total_charge', 'userWallet', 'payment_gatewaies'));
    }



      /**
     * My excrow submit
     *
     * @method POST
     * @return Illuminate\Http\Response
     */
    public function buyTrade(Request $request){
        $validator = Validator::make($request->all(),[
            'trade_id'          => 'required',
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

        return redirect()->route('user.marketplace.evidance', [$validated['trade_id'], $validated['payment_gateway']]);

    }

       /**
     * My marketplace evidence page show
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function evidenceView($trade_id, $gateway_id){
        $page_title = __("Evidence");
        $trade = Trade::findOrFail($trade_id);
        $payment_gateway = PaymentGateway::findOrFail($gateway_id);

        return view('user.sections.marketplace.avidence',compact("page_title", "trade", "payment_gateway"));
    }


      /**
     * My marketplace evidence submit
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function evidenceSubmit(Request $request){
        $validator = Validator::make($request->all(),[
            'trade_id'   => 'required',
            'gateway_id' => 'required',
        ]);

        if($validator->fails()){
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $payment_gateway = PaymentGateway::with('currency')->find($validated['gateway_id']);
        if(!$payment_gateway){
            return back()->with(['error' => [__('Payment gateway not found').'!']]);
        }

        $trade = Trade::find($validated['trade_id']);
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
        $validated['amount'] = $trade->rate;
        $validated['subtotal'] = $trade->rate;
        $charges = TransactionSetting::where('slug', 'trade')->first();
        $intervals = json_decode($charges->intervals);

        $charges = feesAndChargeCalculation($intervals, $validated['subtotal'],$currency->rate);
        $validated['wallet'] = $wallet;
        $validated['fixed_charge']   = $charges['fixed_charge'];
        $validated['percent_charge'] = $charges['percent_charge'];
        $validated['total_charge']   = $charges['total_charge'];
        $validated['total_amount'] = $validated['total_charge'] + $validated['subtotal'];
        $validated['transaction_type'] = PaymentGatewayConst::MARKETPLACE;

        if($wallet->balance < $validated['total_amount']){
            return back()->with(['error' => [__('Insufficient wallet balance').'!']]);
        }

        $payment_fields = $payment_gateway->input_fields ?? [];

        $validation_rules       = $this->generateValidationRules($payment_fields);
        $payment_field_validate = Validator::make($request->all(),$validation_rules)->validate();
        $get_values             = $this->placeValueWithFields($payment_fields,$payment_field_validate);

        try {
            // sender record
            $trx_id = $this->trx_id;
            $transaction_id = $this->insertRecordSender((object) $validated, $validated['trade_id'],$trx_id, $get_values);
            $this->insertChargesSender((object) $validated,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceSender($transaction_id);
            $this->userWalletUpdate($validated['total_amount'], $wallet);

            // seller profit
            $selling_wallet = UserWallet::where('currency_id', $trade->rate_currency_id)->where('user_id',$trade->user_id)->first();



            Trade::find($validated['trade_id'])->update([
                'status' => PaymentGatewayConst::TRADE_STATUSPENDING,
            ]);
            $notification_content = [
                'title'         => __("Trade purchase request"),
                'message'       => "Your trade purchase request has been sent to the admin successfully. Please wait for the admin's response, Purchase Amount "."". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
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
                'total_charge'      => $validated['total_charge'],
                'total_amount'      => $validated['total_amount'],
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
            } catch (Exception $th) {   }

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
                    ]);
                }
            }catch(Exception $e){}


            // receiver record
            $transaction_id = $this->insertRecordReceiver((object) $validated, $validated['trade_id'],$trx_id, $get_values);
            $this->insertChargesReceiver((object) $validated,$transaction_id,NotificationConst::MARKETPLACE_TRANSACTION_ADDED);
            $this->insertDeviceReceiver($transaction_id);


            $notification_content = [
                'title'         => __("Your trade was purchased"),
                'message'       => "Your trade was purchased by @".auth()->user()->username." Please wait for the admin's response. Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
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
            return back()->with(['error' => [__('Something went wrong. Please try again').'!']]);
        }

        return redirect()->route('user.transactions.index')->with(['success' => [__('Transaction created successfully')]]);

    }


     //admin notification
     public function adminNotification($trx_id,$notification_data,$user){

        $notification_content = [
            //email notification
            'subject' =>__("Trade Purchase Request Successfully"),
            'greeting' =>__("Trade Information"),
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


     /**
     * Transactions page show
     *
     * @method GET
     * @return Illuminate\Http\Response
     */
    public function Transactions(){

        $page_title = __("Transactions");
        $transactions = Transaction::with('trade')->orderBy('id', 'desc')
                        ->where('type', PaymentGatewayConst::MARKETPLACE)
                        ->orWhere('user_id', Auth::id())
                        ->whereNot('type', PaymentGatewayConst::TYPEADDMONEY)
                        ->whereNot('type', PaymentGatewayConst::TRADE)
                        ->whereHas('trade', function($q){
                            $q->where('user_id', Auth::id());
                        })
                        ->paginate(9);

        return view('user.sections.marketplace.transactions',compact("page_title", "transactions"));
    }







}
