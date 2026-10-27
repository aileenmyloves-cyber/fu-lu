<?php

namespace App\Http\Controllers\Api\Agent;

use Exception;
use Carbon\Carbon;
use App\Models\AgentWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Constants\GlobalConst;
use App\Http\Helpers\Api\Helpers;
use App\Models\AgentNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Traits\PaymentGateway\Manual;
use App\Traits\PaymentGateway\Stripe;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\PaymentGatewayApi;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\CryptoTransaction;
use App\Traits\PaymentGateway\Authorize;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin\PaymentGatewayCurrency;
use net\authorize\api\contract\v1 as AnetAPI;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use net\authorize\api\controller as AnetController;
use App\Http\Helpers\PaymentGateway as PaymentGatewayHelper;

class AddMoneyController extends Controller
{
    use Stripe,Manual,Authorize;

    public function addMoneyInformation(){
        $transactions = Transaction::agentAuth()->addMoney()->latest()->take(5)->get()->map(function($item){
            $statusInfo = [
                "success"   =>     1,
                "pending"   =>     2,
                "rejected"  =>     3,
            ];
            return[
                'id'                => $item->id,
                'trx'               => $item->trx_id,
                'type'             => PaymentGatewayConst::RECEIVED,
                'gateway_name'      => @$item->currency->name,
                'transaction_type'  => $item->type,
                'request_amount'    => get_amount($item->request_amount,$item->creator_wallet->currency->code,get_wallet_precision($item->creator_wallet->currency)),
                'payable'           => isCrypto($item->payable,$item->currency->currency_code??get_default_currency_code(),$item->currency->gateway->crypto),
                'exchange_rate'     => '1 ' . $item->creator_wallet->currency->code.' = '.isCrypto($item->details->amount->exchange_rate??$item->currency->rate,$item->currency->currency_code??get_default_currency_code(),$item->currency->gateway->crypto),
                'total_charge'      => isCrypto($item->charge->total_charge??0,$item->currency->currency_code??get_default_currency_code(),$item->currency->gateway->crypto),
                'current_balance'   => get_amount($item->available_balance,$item->creator_wallet->currency->code??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                "confirm"           => $item->confirm??false,
                "dynamic_inputs"    => $item->dynamic_inputs,
                "confirm_url"       => $item->confirm_url,
                'status'            => $item->stringStatus->value ,
                'date_time'         => $item->created_at ,
                'status_info'       => (object)$statusInfo ,
                'rejection_reason'  =>$item->reject_reason??"" ,

            ];
        });
        $gateways = PaymentGateway::where('status', 1)->where('slug', PaymentGatewayConst::add_money_slug())->get()->map(function($gateway){
            $currencies = PaymentGatewayCurrency::where('payment_gateway_id',$gateway->id)->get()->map(function($data){
            $precision = get_precision($data->gateway);
              return[
                'id'                    => $data->id,
                'payment_gateway_id'    => $data->payment_gateway_id,
                'crypto'                => $data->gateway->crypto,
                'type'                  => $data->gateway->type,
                'name'                  => $data->name,
                'alias'                 => $data->alias,
                'currency_code'         => $data->currency_code,
                'currency_symbol'       => $data->currency_symbol,
                'image'                 => $data->image,
                'min_limit'             => get_amount($data->min_limit,null,$precision),
                'max_limit'             => get_amount($data->max_limit,null,$precision),
                'percent_charge'        => get_amount($data->percent_charge,null,$precision),
                'fixed_charge'          => get_amount($data->fixed_charge,null,$precision),
                'daily_limit'           => get_amount($data->daily_limit,null,$precision),
                'monthly_limit'         => get_amount($data->monthly_limit,null,$precision),
                'rate'                  => get_amount($data->rate,null,$precision),
                'created_at'            => $data->created_at,
                'updated_at'            => $data->updated_at,
              ];

            });
            return[
                'id'                    => $gateway->id,
                'image'                 => $gateway->image,
                'slug'                  => $gateway->slug,
                'code'                  => $gateway->code,
                'type'                  => $gateway->type,
                'alias'                 => $gateway->alias,
                'crypto'                => $gateway->crypto,
                'supported_currencies'  => $gateway->supported_currencies,
                'status'                => $gateway->status,
                'currencies'            => $currencies

            ];
        });
        $get_remaining_fields = [
            'transaction_type'  =>  PaymentGatewayConst::TYPEADDMONEY,
            'attribute'         =>  PaymentGatewayConst::SEND,
        ];
        $data =[
           'base_curr'            => get_default_currency_code(),
           'base_curr_rate'       => get_amount(get_default_currency_rate(),null,get_wallet_precision()),
           'get_remaining_fields' => (object) $get_remaining_fields,
           'base_url'             => get_asset_url('/'),
           'default_image'        => files_asset_path_basename("default"),
           "image_path"           => files_asset_path_basename('payment-gateways'),
           'gateways'             => $gateways,
           'transactions'         => $transactions,
        ];
        $message =  ['success'=>[__('Add Money Information!')]];
        return Helpers::success($data,$message);
    }
    public function submitData(Request $request) {
        if (is_null($request->source)) {
            $request->merge([
                'source' => GlobalConst::APP,
                'success_url'  => null,
                'cancel_url'   => null,
            ]);
        }

        $validator = Validator::make($request->all(), [
           'currency'        => "required|string|exists:payment_gateway_currencies,alias",
           'wallet_currency' => "required|string|exists:currencies,code",
           'amount'          => "required|numeric|gt:0",
           'source'          => 'required|string|in:'.GlobalConst::WEB.','.GlobalConst::APP,
           'success_url'     => "nullable|string|url",
           'cancel_url'      => "nullable|string|url",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validate();
        $user = authGuardApi()['user'];
        $alias = $request->currency;
        $amount = $request->amount;
        $payment_gateways_currencies = PaymentGatewayCurrency::where('alias',$alias)->whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
            $gateway->where('status', 1);
             })->first();
        if( !$payment_gateways_currencies){
            $error = ['error'=>[__('Gateway Information is not available. Please provide payment gateway currency alias')]];
            return Helpers::error($error);
        }

        $user_wallet = AgentWallet::auth()->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['wallet_currency'])->active();
        })->active()->first();
        if(!$user_wallet) {
            $error = ['error'=>[__('Agent wallet not found')]];
            return Helpers::error($error);
        }
        $precision          = get_precision($payment_gateways_currencies->gateway);
        $wallet_precision   =  get_wallet_precision($user_wallet->currency);

        $gCurrencyRate      =  get_amount($payment_gateways_currencies->rate,null,$precision);
        $wCurrencyRate      = get_amount($user_wallet->currency->rate,null,$wallet_precision);

        $exchange_rate = get_amount(($gCurrencyRate / $wCurrencyRate),null,$precision);
        if($amount < get_amount(($payment_gateways_currencies->min_limit/$exchange_rate),null,$wallet_precision) || $amount > get_amount(($payment_gateways_currencies->max_limit/ $exchange_rate),null,$wallet_precision)) {
            $error = ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);
        }
        try{
            $instance = PaymentGatewayApi::init($request->all())->type(PaymentGatewayConst::TYPEADDMONEY)->gateway()->api()->get();
            if ($instance['response'] instanceof \Illuminate\Http\JsonResponse) {
                return $instance['response'];
            }
            if( $instance['distribute'] == "tatumInitApi" ){
                $data = [
                    'gateway_info' =>$instance['response'],
                    'payment_info' =>[
                        'request_amount' => get_amount($instance['amount']->requested_amount,$instance['amount']->default_currency,8),
                        'exchange_rate' => "1".' '.$instance['amount']->default_currency.' = '.get_amount($instance['amount']->sender_cur_rate,$instance['amount']->sender_cur_code,8),
                        'total_charge' => get_amount($instance['amount']->total_charge,$instance['amount']->sender_cur_code,8),
                        'will_get' => get_amount($instance['amount']->will_get,$instance['amount']->default_currency,8),
                        'payable_amount' =>  get_amount($instance['amount']->total_amount,$instance['amount']->sender_cur_code,8),
                    ]
                ];
                $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                return Helpers::success($data,$message);

            }
            $trx = $instance['response']['id']??$instance['response']['trx']??$instance['response']['reference_id']??$instance['response']['order_id']??$instance['response']['temp_identifier']??$instance['response']??'';

            $temData = TemporaryData::where('identifier',$trx)->first();
            if(!$temData){
                $error = ['error'=>["Invalid Request"]];
                return Helpers::error($error);
            }
            $payment_gateway_currency = PaymentGatewayCurrency::where('id', $temData->data->currency)->first();
            $payment_gateway = PaymentGateway::where('id', $temData->data->gateway)->first();
            $precision = get_precision($payment_gateway);
            $payment_information =[
                'trx'                   => $temData->identifier,
                'gateway_currency_name' => $payment_gateway_currency->name,
                'request_amount'        => get_amount($temData->data->amount->requested_amount,$temData->data->amount->receiver_cur_code,$wallet_precision),
                'exchange_rate'         => "1".' '.$temData->data->amount->sender_cur_code.' = '.get_amount($temData->data->amount->exchange_rate,$temData->data->amount->sender_cur_code,$precision),
                'total_charge'          => get_amount($temData->data->amount->total_charge,$temData->data->amount->sender_cur_code,$precision),
                'will_get'              => get_amount($temData->data->amount->will_get,$temData->data->amount->receiver_cur_code,$wallet_precision),
                'payable_amount'        => get_amount($temData->data->amount->total_amount,$temData->data->amount->sender_cur_code,$precision),
            ];
            if($payment_gateway->type == "AUTOMATIC") {
                if($temData->type == PaymentGatewayConst::STRIPE) {
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => @$temData->data->response->link."?prefilled_email=".@$user->email,
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }elseif($temData->type == PaymentGatewayConst::PAYSTACK) {
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_informations' => $payment_information,
                        'url' => @$instance['response']['link'],
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }elseif($temData->type == PaymentGatewayConst::COINGATE) {
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => $instance['response']['link'],
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }elseif($temData->type == PaymentGatewayConst::SSLCOMMERZ) {
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => $instance['response']['link'],
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }else if($temData->type == PaymentGatewayConst::PAYPAL) {
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => @$temData->data->response->links,
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data, $message);

                }else if($temData->type == PaymentGatewayConst::FLUTTER_WAVE) {

                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => @$temData->data->response->link,
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }else if($temData->type == PaymentGatewayConst::RAZORPAY){

                    $data =[
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_information'  => $payment_information,
                        'url'                   => $instance['response']['redirect_url'],
                        'method'                => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);

                }else if($temData->type == PaymentGatewayConst::PAGADITO){

                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => @$temData->data->response->value,
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);

                }else if($temData->type == PaymentGatewayConst::PERFECT_MONEY){

                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_information'  => $payment_information,
                        'url'                   => $instance['response']['redirect_url'],
                        'method'                => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);

                }elseif($temData->type == PaymentGatewayConst::BKASH) {
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_information' => $payment_information,
                        'url' => @$instance['response']['link'],
                        'method' => "get",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }elseif($temData->type == PaymentGatewayConst::AUTHORIZE) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_information'   => $payment_information,
                        'url'                   => @$instance['response']['redirect_url'] ?? '',
                        'method'                => "POST",
                    ];
                    $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                    return Helpers::success($data,$message);
                }
            }elseif($payment_gateway->type == "MANUAL"){
                    $data =[
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'details' => $payment_gateway->desc??null,
                        'input_fields' => $payment_gateway->input_fields??null,
                        'payment_information' => $payment_information,
                        'url' => route('agent.api.manual.payment.confirmed'),
                        'method' => "post",
                        ];
                        $message =  ['success'=>[__('Add Money Inserted Successfully')]];
                        return Helpers::success($data, $message);
            }else{
                $error = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }

        }catch(Exception $e) {
            $error = ['error'=>[$e->getMessage()??__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        // return $instance;
    }

     public function success(Request $request, $gateway)
    {
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";
        $checkTempData = TemporaryData::where("type", $gateway)->where("identifier", $token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->validate_data->success_url ?? '';
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';

        if (!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }

        $checkTempData = $checkTempData->toArray();
        try {
            PaymentGatewayApi::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive();
        } catch (Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($success_url)
                : Helpers::onlysuccess(['success' => [__("Payment Successful, Please Go Back Your App")]]);
    }
    public function cancel(Request $request, $gateway)
    {
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";

        $checkTempData = TemporaryData::where("type",$gateway)->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';


        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }

        if( $token){
            TemporaryData::where("identifier",$token)->delete();
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::onlysuccess(['success' => [__('Transaction Canceled Successfully')]]);
    }
    public function flutterwaveCallback()
    {
        $status = request()->status;
        $requestData = request()->tx_ref;
        $token = $requestData;
        $checkTempData = TemporaryData::where("type",'flutterwave')->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->validate_data->success_url ?? '';
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';

        if(!$checkTempData){
                return  $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }

        if ($status ==  'successful' || $status == 'completed') {
            $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data = Flutterwave::verifyTransaction($transactionID);

            $checkTempData = $checkTempData->toArray();
            try{
                PaymentGatewayApi::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('flutterwave');
            }catch(Exception $e) {
                return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
            }
            return $source == GlobalConst::WEB
                ? redirect()->away($success_url)
                : Helpers::onlysuccess(['success' => [__("Payment Successful, Please Go Back Your App")]]);

        }elseif ($status ==  'cancelled'){
            return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::onlysuccess(['success' => [__('Transaction Canceled Successfully')]]);
        }else{
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }
     //stripe success
    public function stripePaymentSuccess($trx){
        $token = $trx;
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::STRIPE)->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->validate_data->success_url ?? '';
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';

        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();
        try{
            PaymentGatewayApi::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('stripe');
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($success_url)
                : Helpers::onlysuccess(['success' => [__("Payment Successful, Please Go Back Your App")]]);

    }
    //sslcommerz success
    public function sllCommerzSuccess(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->validate_data->success_url ?? '';
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';


        if(!$checkTempData) {
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }

        $checkTempData = $checkTempData->toArray();

        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        if( $data['status'] != "VALID"){
            return $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__('Transaction failed')]]);
        }
        try{
            PaymentGatewayApi::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('sslcommerz');
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($success_url)
                : Helpers::onlysuccess(['success' => [__("Payment Successful, Please Go Back Your App")]]);
    }
    //sslCommerz fails
    public function sllCommerzFails(Request $request){
        $data = $request->all();

        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';


        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();

        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;

        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        if( $data['status'] == "FAILED"){
            return $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__('Transaction failed')]]);
        }

    }
    //sslCommerz canceled
    public function sllCommerzCancel(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';


        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();


        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;

        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        if( $data['status'] != "VALID"){
            TemporaryData::destroy($checkTempData['id']);
            return $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__('Transaction failed')]]);
        }
    }
    //coingate
    public function coinGateSuccess(Request $request, $gateway){
        try{
            $token = $request->token;
            $checkTempData = TemporaryData::where("type",PaymentGatewayConst::COINGATE)->where("identifier",$token)->first();

            $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
            $success_url = $checkTempData->data->validate_data->success_url ?? '';
            $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';


            if(!$checkTempData) {
                if(Transaction::where('callback_ref',$token)->exists()) {
                    return $source == GlobalConst::WEB
                                ? redirect()->away($success_url)
                                : Helpers::onlysuccess(['success' => [__('Payment Successful, Please Go Back Your App')]]);
                }else{
                    return  $source == GlobalConst::WEB
                                ? redirect()->away($cancel_url)
                                : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
                }
            }
            $update_temp_data = json_decode(json_encode($checkTempData->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $checkTempData->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $checkTempData->toArray();
            PaymentGatewayApi::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('coingate');
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($success_url)
                : Helpers::onlysuccess(['success' => [__("Payment Successful, Please Go Back Your App")]]);
    }
    public function coinGateCancel(Request $request, $gateway){
        $token = $request->token ?? "";
        $checkTempData = TemporaryData::where("type",$gateway)->where("identifier",$token)->first();

        $source      = $checkTempData->data->validate_data->source ?? GlobalConst::APP;
        $cancel_url  = $checkTempData->data->validate_data->cancel_url ?? '';


        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }

        if( $token){
            TemporaryData::where("identifier",$token)->delete();
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::onlysuccess(['success' => [__('Transaction Canceled Successfully')]]);
    }

    public function cryptoPaymentConfirm(Request $request, $trx_id)
    {
        $transaction = Transaction::where('trx_id',$trx_id)->where('status', PaymentGatewayConst::STATUSWAITING)->first();
        if(!$transaction){
            $message = ['error' => [__("Transaction Failed. The record didn't save properly. Please try again")]];
            return Helpers::error($message);
        }
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


        $validator = [];
        if(count($validation_rules) > 0) {
            $validator = Validator::make($request->all(), $validation_rules);
            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }
            $validated =  $validator->validate();
        }

        if(!isset($validated['txn_hash'])){
            $message = ['error' => [__('Transaction hash is required for verify')]];
            return Helpers::error($message);
        }

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

        if(!$crypto_transaction){
            $message = ['error' => [__('Transaction hash is not valid! Please input a valid hash')]];
            return Helpers::error($message);
        }

        if($crypto_transaction->amount >= $transaction->total_payable == false) {
            if(!$crypto_transaction){
                $message = ['error' => [__("Insufficient amount added. Please contact with system administrator")]];
                return Helpers::error($message);
            }
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
            $message = ['error' => [__('Something went wrong! Please try again')]];
            return Helpers::error($message);
        }

        $message = ['success' => [__('Payment Confirmation Success')]];
        return Helpers::onlysuccess($message);
    }

    public function redirectBtnPay(Request $request, $gateway)
    {
        try{
            return PaymentGatewayApi::init([])->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return Helpers::error($message);
        }
    }

    public function successGlobal(Request $request, $gateway){

        try{
            $token = PaymentGatewayApi::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();

            $source      = $temp_data->data->validate_data->source ?? GlobalConst::APP;
            $success_url = $temp_data->data->validate_data->success_url ?? '';
            $cancel_url  = $temp_data->data->validate_data->cancel_url ?? '';

            if(!$temp_data) {
                if(Transaction::where('callback_ref',$token)->exists()) {
                    return $source == GlobalConst::WEB
                            ? redirect()->away($success_url)
                            : Helpers::onlysuccess(['success' => [__('Payment Successful, Please Go Back Your App')]]);
                }else {
                    return  $source == GlobalConst::WEB
                                ? redirect()->away($cancel_url)
                                : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
                }
            }

            $update_temp_data = json_decode(json_encode($temp_data->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $temp_data->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $temp_data->toArray();
            $instance = PaymentGatewayApi::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive($temp_data['type']);

            // return $instance;
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($success_url)
                : Helpers::onlysuccess(['success' => [__("Payment Successful, Please Go Back Your App")]]);
    }
    public function cancelGlobal(Request $request,$gateway) {
        $token = PaymentGatewayApi::getToken($request->all(),$gateway);
        $temp_data = TemporaryData::where("identifier",$token)->first();

        $source      = $temp_data->data->validate_data->source ?? GlobalConst::APP;
        $cancel_url  = $temp_data->data->validate_data->cancel_url ?? '';
        try{
            if($temp_data != null) {
                $temp_data->delete();
            }
        }catch(Exception $e) {
            // Handel error
        }

        return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::onlysuccess(['success' => [__('Transaction Canceled Successfully')]]);
    }
    public function postSuccess(Request $request, $gateway)
    {

        try{
            $token = PaymentGatewayApi::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            $source      = $temp_data->data->order_data->source ?? GlobalConst::APP;
            $cancel_url  = $temp_data->data->order_data->cancel_url ?? '';


            if($temp_data && $temp_data->data->creator_guard != 'agent_api') {
                Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
            }
        }catch(Exception $e) {
            return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::error(['error' => [__('Transaction failed')]]);
        }

        return $this->successGlobal($request, $gateway);
    }
    public function postCancel(Request $request, $gateway)
    {
        try{
            $token = PaymentGatewayApi::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            $source      = $temp_data->data->order_data->source ?? GlobalConst::APP;
            $cancel_url  = $temp_data->data->order_data->cancel_url ?? '';

            if($temp_data && $temp_data->data->creator_guard != 'agent_api') {
                Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
            }
        }catch(Exception $e) {
            return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::error(['error' => [__('Transaction failed')]]);
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
     * Method function authorize payment submit
     * @param Illuminate\Http\Request $request
     */
    public function authorizePaymentSubmit(Request $request){
        $validator          = Validator::make($request->all(),[
            'identifier'    => 'required',
            'card_number'   => 'required',
            'date'          => 'required',
            'code'          => 'required'
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated          = $validator->validate();

        $temp_data          = TemporaryData::where('identifier',$validated['identifier'])->first();
        if(!$temp_data){
            $error = ['error'=>[__('Sorry ! Data not found.')]];
            return Helpers::error($error);
        }

        $request->merge([
            'token' => $validated['identifier']
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
        $customerAddress->setFirstName(auth()->user()->firstname);
        $customerAddress->setLastName(auth()->user()->lastname);
        $customerAddress->setCompany($basic_settings->site_name);
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
                    $error = ['error'=>[__('Transaction Failed')]];
                    return Helpers::error($error);

                    if ($tresponse->getErrors() != null) {
                        $error = ['error'=>[$tresponse->getErrors()[0]->getErrorText()]];
                        return Helpers::error($error);
                    }
                }
            }else {
                    $error = ['error'=>[__('Transaction Failed')]];
                    return Helpers::error($error);
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $error = ['error'=>[$tresponse->getErrors()[0]->getErrorText()]];
                    return Helpers::error($error);
                } else {
                    $error = ['error'=>[$response->getMessages()->getMessage()[0]->getText()]];
                    return Helpers::error($error);
                }
            }
        }else {
            $error = ['error'=>[__('No response returned')]];
            return Helpers::error($error);
        }
    }

}