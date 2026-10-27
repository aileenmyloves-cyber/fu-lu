<?php

namespace App\Http\Controllers\Agent;

use Exception;
use App\Models\AgentWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Constants\GlobalConst;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\AgentNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Traits\PaymentGateway\Manual;
use App\Traits\PaymentGateway\Stripe;
use Illuminate\Http\RedirectResponse;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\CryptoTransaction;
use Illuminate\Support\Facades\Session;
use App\Traits\PaymentGateway\Authorize;
use App\Traits\PaymentGateway\RazorTrait;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\PaymentGatewayCurrency;
use net\authorize\api\contract\v1 as AnetAPI;
use App\Providers\Admin\BasicSettingsProvider;
use App\Traits\PaymentGateway\SslcommerzTrait;
use App\Traits\PaymentGateway\FlutterwaveTrait;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use net\authorize\api\controller as AnetController;
use App\Http\Helpers\PaymentGateway as PaymentGatewayHelper;

class AddMoneyController extends Controller
{
    use Stripe,Manual,FlutterwaveTrait,RazorTrait,SslcommerzTrait,Authorize;

    protected $basic_settings;
    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }

    public function index() {
        $page_title = __("Add Money");
        $user_wallets = AgentWallet::auth()->get();
        $wallet_currencies = Currency::whereIn('id',$user_wallets->pluck('currency_id')->toArray())->get();

        $payment_gateways_currencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
            $gateway->where('status', 1);
        })->get();
        $transactions = Transaction::agentAuth()->addMoney()->latest()->take(10)->get();
        return view('agent.sections.add-money.index',compact("page_title","transactions","payment_gateways_currencies","wallet_currencies"));
    }
    public function submit(Request $request) {
        $user = userGuard()['user'];
        $validated = Validator::make($request->all(),[
            'pin'   => $this->basic_settings->agent_pin_verification == true ? 'required|digits:4' : 'nullable',
        ])->validate();
        try{
            //check user pin
            if( $this->basic_settings->agent_pin_verification == true){
                $pin_status = pin_verification($user,$validated['pin']);
                if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
            }

            $instance = PaymentGatewayHelper::init($request->all())->type(PaymentGatewayConst::TYPEADDMONEY)->gateway()->render();
        }catch(Exception $e) {
            return back()->with(['error' => [__($e->getMessage())]]);
        }
        return $instance;
    }
    public function success(Request $request, $gateway){
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";
        $checkTempData = TemporaryData::where("type",$gateway)->where("identifier",$token)->first();
        if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
        $checkTempData = $checkTempData->toArray();

        try{
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive();
        }catch(Exception $e) {

            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }
        return redirect()->route("agent.add.money.index")->with(['success' => [__("Successfully Added Money")]]);
    }

    public function cancel(Request $request, $gateway) {
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";
        if( $token){
            TemporaryData::where("identifier",$token)->delete();
        }
        return redirect()->route('agent.add.money.index');
    }
    public function manualPayment(){
        $tempData = Session::get('identifier');
        $hasData = TemporaryData::where('identifier', $tempData)->first();
        if(!$hasData){
            return redirect()->route('agent.add.money.index');
        }
        $gateway = PaymentGateway::manual()->where('slug',PaymentGatewayConst::add_money_slug())->where('id',$hasData->data->gateway)->first();
        $page_title = __("Manual Payment")." ".' ( '.$gateway->name.' )';

        return view('agent.sections.add-money.manual.payment_confirmation',compact("page_title","hasData",'gateway'));
    }
    public function flutterwaveCallback()
    {
        $status = request()->status;
        //if payment is successful
        if ($status ==  'successful' || $status == 'completed') {
            $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data = Flutterwave::verifyTransaction($transactionID);

            $requestData = request()->tx_ref;
            $token = $requestData;

            $checkTempData = TemporaryData::where("type",'flutterwave')->where("identifier",$token)->first();

            if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);

            $checkTempData = $checkTempData->toArray();

            try{
                PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('flutterWave');
            }catch(Exception $e) {
                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }
            return redirect()->route("agent.add.money.index")->with(['success' => [__("Successfully Added Money")]]);

        }
        elseif ($status ==  'cancelled'){
            return redirect()->route('agent.add.money.index')->with(['error' => [__('Add money cancelled')]]);
        }
        else{
            return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction failed")]]);
        }
    }
    //stripe success
    public function stripePaymentSuccess($trx){
        $token = $trx;
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::STRIPE)->where("identifier",$token)->first();
        if(!$checkTempData) return redirect()->route('user.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
        $checkTempData = $checkTempData->toArray();
        try{
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('stripe');
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return redirect()->route("agent.add.money.index")->with(['success' => [__('Successfully Added Money')]]);
    }
    //sslcommerz success
    public function sllCommerzSuccess(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
        $checkTempData = $checkTempData->toArray();
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;

        $user = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if( $data['status'] != "VALID"){
            return redirect()->route("agent.add.money.index")->with(['error' => [__('Added Money Failed')]]);
        }
        try{
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('sslcommerz');
        }catch(Exception $e) {
            return back()->with(['error' => ["Something went wrong! Please try again."]]);
        }
        return redirect()->route("agent.add.money.index")->with(['success' => ['Successfully Added Money']]);
    }
    //sslCommerz fails
    public function sllCommerzFails(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
        $checkTempData = $checkTempData->toArray();
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;
        $user = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if( $data['status'] == "FAILED"){
            TemporaryData::destroy($checkTempData['id']);
            return redirect()->route("agent.add.money.index")->with(['error' => [__('Added Money Failed')]]);
        }

    }
    //sslCommerz canceled
    public function sllCommerzCancel(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
        $checkTempData = $checkTempData->toArray();
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;
        $user = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if( $data['status'] != "VALID"){
            TemporaryData::destroy($checkTempData['id']);
            return redirect()->route("agent.add.money.index")->with(['error' => [__('Add money cancelled')]]);
        }
    }
    //coingate response start
    public function coinGateSuccess(Request $request, $gateway){

        try{
            $token = $request->token;
            $checkTempData = TemporaryData::where("type",PaymentGatewayConst::COINGATE)->where("identifier",$token)->first();
            if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);

            if(Transaction::where('callback_ref', $token)->exists()) {
                if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['success' => [__('Transaction request sended successfully!')]]);
            }else {
                if(!$checkTempData) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
            }
            $update_temp_data = json_decode(json_encode($checkTempData->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $checkTempData->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $checkTempData->toArray();
            PaymentGatewayHelper::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('coingate');
        }catch(Exception $e) {
            return redirect()->route("agent.add.money.index")->with(['error' => [__('Something went wrong! Please try again.')]]);
        }
        return redirect()->route("agent.add.money.index")->with(['success' => [__('Successfully Added Money')]]);
    }
    public function coinGateCancel(Request $request, $gateway){
        if($request->has('token')) {
            $identifier = $request->token;
            if($temp_data = TemporaryData::where('identifier', $identifier)->first()) {
                $temp_data->delete();
            }
        }
        return redirect()->route("user.add.money.index")->with(['error' => [__('Add money cancelled')]]);
    }
    public function callback(Request $request,$gateway){
        $callback_token = $request->get('token');
        $callback_data = $request->all();
        try{
            PaymentGatewayHelper::init([])->type(PaymentGatewayConst::TYPEADDMONEY)->handleCallback($callback_token,$callback_data,$gateway);
        }catch(Exception $e) {
            // handle Error
            logger($e);
        }
    }
    //coingate response end
    public function cryptoPaymentAddress(Request $request, $trx_id) {
        $page_title =__( "Crypto Payment Address");
        $transaction = Transaction::where('trx_id', $trx_id)->firstOrFail();
        if($transaction->gateway_currency->gateway->isCrypto() && $transaction->details?->payment_info?->receiver_address ?? false) {
            return view('agent.sections.add-money.payment.crypto.address', compact(
                'transaction',
                'page_title',
            ));
        }

        return abort(404);
    }
    public function cryptoPaymentConfirm(Request $request, $trx_id)
    {
        $transaction = Transaction::where('trx_id',$trx_id)->where('status', PaymentGatewayConst::STATUSWAITING)->firstOrFail();
        $user =  $transaction->agent;
        $gateway_currency =  $transaction->currency->alias;

        $request_data = $request->merge([
            'currency' => $gateway_currency,
            'amount' => $transaction->request_amount,
            'wallet_currency' => $transaction->details->charge->receiver_cur_code ?? get_default_currency_code(),
        ]);
        $output = PaymentGatewayHelper::init($request_data->all())->type(PaymentGatewayConst::TYPEADDMONEY)->gateway()->get();

        $dy_input_fields = $transaction->details->payment_info->requirements ?? [];
        $validation_rules = $this->generateValidationRules($dy_input_fields);

        $validated = [];
        if(count($validation_rules) > 0) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        }

        if(!isset($validated['txn_hash'])) return back()->with(['error' => [__('Transaction hash is required for verify')]]);

        $receiver_address = $transaction->details->payment_info->receiver_address ?? "";

        // check hash is valid or not
        $crypto_transaction = CryptoTransaction::where('txn_hash', $validated['txn_hash'])
                                                ->where('receiver_address', $receiver_address)
                                                ->where('asset',$transaction->gateway_currency->currency_code)
                                                ->where(function($query) {
                                                    return $query->where('transaction_type',"Native")
                                                                ->orWhere('transaction_type', "native");
                                                })
                                                ->where('status',PaymentGatewayConst::NOT_USED)
                                                ->first();

        if(!$crypto_transaction) return back()->with(['error' => [__('Transaction hash is not valid! Please input a valid hash')]]);

        if($crypto_transaction->amount >= $transaction->total_payable == false) {
            if(!$crypto_transaction) return back()->with(['error' => [__("Insufficient amount added. Please contact with system administrator")]]);
        }

        DB::beginTransaction();
        try{

            // Update user wallet balance
            DB::table($transaction->creator_wallet->getTable())
                ->where('id',$transaction->creator_wallet->id)
                ->increment('balance',$transaction->request_amount);

            // update crypto transaction as used
            DB::table($crypto_transaction->getTable())->where('id', $crypto_transaction->id)->update([
                'status'        => PaymentGatewayConst::USED,
            ]);

            // update transaction status
            $transaction_details = json_decode(json_encode($transaction->details), true);
            $transaction_details['payment_info']['txn_hash'] = $validated['txn_hash'];

            DB::table($transaction->getTable())->where('id', $transaction->id)->update([
                'details'       => json_encode($transaction_details),
                'status'        => PaymentGatewayConst::STATUSSUCCESS,
                'available_balance'        => $transaction->available_balance + $transaction->request_amount,
            ]);

             //notification
             $notification_content = [
                'title'         => __("Add Money"),
                'message'       => __("Your Wallet")." (".$output['wallet']->currency->code.")  ".__("balance  has been added")." ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => get_image($user->image,'agent-profile'),
            ];

            AgentNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'agent_id'  =>  $user->id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            if($this->basic_settings->agent_push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'agent',
                    ])->send();
                }catch(Exception $e) {}
            }
            //admin notification
            $notification_content['title'] = __("Add Money ").' '.$output['amount']->requested_amount.' '.$output['amount']->default_currency.'  '.__('By').' '. $output['currency']->name.' ('.$user->username.')';
            AdminNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'admin_id'  => 1,
                'message'   => $notification_content,
            ]);

            DB::commit();

        }catch(Exception $e) {
            DB::rollback();
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Payment Confirmation Success')]]);
    }
    /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function redirectBtnPay(Request $request, $gateway)
    {
        try{
            return PaymentGatewayHelper::init([])->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) {
            return redirect()->route('agent.add.money.index')->with(['error' => [$e->getMessage()]]);
        }
    }
    public function successGlobal(Request $request, $gateway){

        try{
            $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();

            if(Transaction::where('callback_ref', $token)->exists()) {
                if(!$temp_data) return redirect()->route('agent.add.money.index')->with(['success' => [__('Transaction request sended successfully!')]]);
            }else {
                if(!$temp_data) return redirect()->route('agent.add.money.index')->with(['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]]);
            }
            $update_temp_data = json_decode(json_encode($temp_data->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $temp_data->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $temp_data->toArray();
            $instance = PaymentGatewayHelper::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive($temp_data['type']);
            if($instance instanceof RedirectResponse) return $instance;
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route("agent.add.money.index")->with(['success' => [__('Successfully Added Money')]]);
    }
    public function cancelGlobal(Request $request, $gateway) {
        $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
        if($temp_data = TemporaryData::where("identifier",$token)->first()) {
            $temp_data->delete();
        }
        return redirect()->route('user.add.money.index');
    }
    public function postSuccess(Request $request, $gateway)
    {
        try{
            $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
        }catch(Exception $e) {
            return redirect()->route('index');
        }
        return $this->successGlobal($request, $gateway);
    }
    public function postCancel(Request $request, $gateway)
    {
        try{
            $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
        }catch(Exception $e) {
            return redirect()->route('index');
        }
        return $this->cancelGlobal($request, $gateway);
    }
    public function redirectUsingHTMLForm(Request $request, $gateway)
    {
        $temp_data = TemporaryData::where('identifier', $request->token)->first();
        if(!$temp_data || $temp_data->data->action_type != PaymentGatewayConst::REDIRECT_USING_HTML_FORM) return back()->with(['error' => ['Request token is invalid!']]);
        $redirect_form_data = $temp_data->data->redirect_form_data;
        $action_url         = $temp_data->data->action_url;
        $form_method        = $temp_data->data->form_method;

        return view('payment-gateway.redirect-form', compact('redirect_form_data', 'action_url', 'form_method'));
    }

    /**
     * Method for view authorize card view page
     * @param Illuminate\Http\Request $request,$identifier
     */
    public function authorizeCardInfo(Request $request,$identifier){
        $page_title         = __("Authorize card Information");
        $temp_data          = TemporaryData::where('identifier',$identifier)->first();

        return view('agent.sections.add-money.automatic.authorize',compact(
            'page_title',
            'temp_data'
        ));


    }

    /**
     * Method function authorize payment submit
     * @param Illuminate\Http\Request $request, $identifier
     */
    public function authorizePaymentSubmit(Request $request,$identifier){

        $temp_data          = TemporaryData::where('identifier',$identifier)->first();
        if(!$temp_data) return back()->with(['error' => ['Sorry ! Data not found.']]);

        $validator          = Validator::make($request->all(),[
            'card_number'   => 'required',
            'date'          => 'required',
            'code'          => 'required'
        ]);

        if($validator->fails()) return back()->withErrors($validator)->withInput($request->all());
        $validated          = $validator->validate();


        $request->merge([
            'token' => $identifier
        ]);

        $request_data = $request->all();



        $gateway_credentials          = $this->authorizeCredentials($temp_data);


        $basic_settings               = BasicSettings::first();

        $validated['card_number']     = str_replace(' ', '', $validated['card_number']);

        $convert_date   = explode('/', $validated['date']);
        $month          = $convert_date[1];
        $year           = $convert_date[0];

        $current_year   = substr(date('Y'), 0, 2);
        $full_year      = $current_year . $year;

        $validated['date'] = $full_year . '-' . $month;

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($gateway_credentials->app_login_id);
        $merchantAuthentication->setTransactionKey($gateway_credentials->transaction_key);
        $amount = floatval($temp_data->data->amount->total_amount);



        // Set the transaction's refId
        $refId = 'ref' . time();

        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();

        $creditCard->setCardNumber($validated['card_number']);
        $creditCard->setExpirationDate($validated['date']);
        $creditCard->setCardCode($validated['code']);


        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        $generate_invoice_number        = generate_random_string_number(10) . time();

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($generate_invoice_number);
        $order->setDescription("Add Money");

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName(auth()->user()->firstname ?? '');
        $customerAddress->setLastName(auth()->user()->lastname ?? '');
        $customerAddress->setCompany($basic_settings->site_name ?? '');
        $customerAddress->setAddress(auth()->user()->address->address ?? '');
        $customerAddress->setCity(auth()->user()->address->city ?? '');
        $customerAddress->setState(auth()->user()->address->state ?? '');
        $customerAddress->setZip(auth()->user()->address->zip ?? '');
        $customerAddress->setCountry(auth()->user()->address->country ?? '');


        $make_customer_id       = auth()->user()->id . $gateway_credentials->code;
        // Set the customer's identifying information
        $customerData = new AnetAPI\CustomerDataType();
        $customerData->setType("individual");
        $customerData->setId($make_customer_id);
        $customerData->setEmail(auth()->user()->email);

        // Add values for transaction settings
        $duplicateWindowSetting = new AnetAPI\SettingType();
        $duplicateWindowSetting->setSettingName("duplicateWindow");
        $duplicateWindowSetting->setSettingValue("60");


        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setCustomer($customerData);
        $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);


        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);



        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);

        if($gateway_credentials->mode == GlobalConst::ENV_SANDBOX){
            $environment = \net\authorize\api\constants\ANetEnvironment::SANDBOX;

        }else{
            $environment = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        }
        $response   = $controller->executeWithApiResponse($environment);
        if ($response != null) {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $request_data = new Request($request_data);
                    return $this->successGlobal($request_data, PaymentGatewayConst::AUTHORIZE);
                }else {
                    return back()->with(['error' => ['Transaction Failed']]);
                    if ($tresponse->getErrors() != null) {
                        return back()->with(['error' => [$tresponse->getErrors()[0]->getErrorText()]]);
                    }
                }
            }else {
                return back()->with(['error' => ['Transaction Failed']]);
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getErrors() != null) {
                    return back()->with(['error' => [$tresponse->getErrors()[0]->getErrorText()]]);
                } else {
                    return back()->with(['error' => [$response->getMessages()->getMessage()[0]->getText()]]);
                }
            }
        }else {
            return redirect()->back()->with(['error' => ['No response returned']]);
        }


    }

}
