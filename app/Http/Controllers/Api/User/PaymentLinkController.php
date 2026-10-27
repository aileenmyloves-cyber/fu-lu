<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\GlobalConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\PayLinkPaymentGateway;
use App\Models\Admin\Currency;
use App\Models\Admin\GatewayAPi;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\TransactionSetting;
use App\Models\Merchants\MerchantWallet;
use App\Models\PaymentLink;
use App\Models\TemporaryData;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;
use App\Traits\PayLink\WalletTransactionTrait;
use App\Traits\PaymentGateway\StripeLinkPayment;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

class PaymentLinkController extends Controller
{
    use StripeLinkPayment,WalletTransactionTrait;
    /**
     * Payment link List
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function index(){
        $payment_links = PaymentLink::auth()->orderBy('id', 'desc')->get()->map(function($data){
            return [
                'id'            => $data->id,
                'currency'      => $data->currency,
                'currency_name' => $data->currency_name,
                'currency_symbol' => $data->currency_symbol,
                'country'       => $data->country,
                'type'          => $data->type,
                'token'         => $data->token,
                'title'         => $data->title,
                'image'         => $data->image,
                'details'       => $data->details,
                'limit'         => $data->limit,
                'min_amount'    => $data->min_amount ? get_amount($data->min_amount,null,$data->precision) : 0,
                'max_amount'    => $data->max_amount ? get_amount($data->max_amount,null,$data->precision): 0,
                'price'         => $data->price ? get_amount($data->price,null,$data->precision) : 0,
                'qty'           => $data->qty,
                'status'        => $data->status,
                'string_status' => $data->stringStatus->value,
                'shareLink' =>     setRoute('payment-link.share', $data->token),
                'created_at'    => $data->created_at,
            ];
        });

        $data = [
            'base_url'      => url('/'),
            'default_image' => get_files_public_path('default'),
            'image_path'    => get_files_public_path('payment-link-image'),
            'currency_data' => Currency::active()->get(),
            'payment_links' => $payment_links,
        ];
        $message =  ['success'=>[__('Data Fetch Successful')]];
        return Helpers::success($data,$message);

    }

    /**
     * Payment link store
     *
     * @param Illuminate\Http\Request $request
     * @method POST
     * @return Illuminate\Http\Request
     */
    public function store(Request $request){

        $token = generate_unique_string('payment_links', 'token', 60);

        if($request->type == PaymentGatewayConst::LINK_TYPE_PAY){

            $validator = Validator::make($request->all(), [
                'currency'        => 'required|string',
                'currency_symbol' => 'required|string',
                'country'         => 'required|string',
                'currency_name'   => 'required|string',
                'title'           => 'required|string|max:180',
                'type'            => 'required|string',
                'details'         => 'nullable|string',
                'limit'           => 'nullable',
                'min_amount'      => 'nullable|numeric|min:0.1',
                'max_amount'      => 'nullable|numeric|gt:min_amount',
                'image'           => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
            ]);

            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }

            $validated = $validator->validated();
            $validated = Arr::except($validated, ['image']);
            $validated['limit'] = $request->limit ? 1 : 2;
            $validated['token'] = $token;
            $validated['status'] = 1;
            $validated['user_id'] = Auth::id();

            try {
                $payment_link = PaymentLink::create($validated);

                if($request->hasFile('image')) {
                    try{
                        $image = upload_file($request->image,'payment-link-image');
                        $upload_image = upload_files_from_path_dynamic([$image['dev_path']],'payment-link-image');
                        delete_file($image['dev_path']);
                        $payment_link->update([
                            'image'  => $upload_image,
                        ]);
                    }catch(Exception $e) {
                        $error = ['error'=>[__("Something went wrong! Please try again.")]];
                        return Helpers::error($error);
                    }
                }

            } catch (\Exception $th) {
                $error = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }

        }else{
            $validator = Validator::make($request->all(), [
                'sub_currency'    => 'required',
                'currency_symbol' => 'required',
                'currency_name'   => 'required',
                'country'         => 'required',
                'sub_title'       => 'required|max:180',
                'type'            => 'required',
                'price'           => 'nullable:numeric',
                'qty'             => 'nullable:integer',
            ]);


            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }

            $validated = $validator->validated();
            $validated['currency'] = $validated['sub_currency'];
            $validated['title'] = $validated['sub_title'];
            $validated['token'] = $token;
            $validated['status'] = 1;
            $validated['user_id'] = Auth::id();

            $validated = Arr::except($validated, ['sub_currency','sub_title']);

            try {
                $payment_link = PaymentLink::create($validated);
            } catch (\Exception $th) {
                $error = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }
        }

        $data = [
            'payment_link' => $payment_link,
        ];
        $message =  ['success'=>[__('payment Link Created Successfully')]];
        return Helpers::success($data,$message);
    }

     /**
     * Payment link store
     *
     * @param Illuminate\Http\Request $request
     * @method POST
     * @return Illuminate\Http\Request
     */
    public function edit(Request $request){

        $paymentLink = PaymentLink::auth()->find($request->target);

        if(empty($paymentLink)){
            $error = ['error'=>['Invalid Request!']];
            return Helpers::error($error);
        }

        $payment_link_data = [
                'id'              => $paymentLink->id,
                'currency'        => $paymentLink->currency,
                'currency_name'   => $paymentLink->currency_name,
                'currency_symbol' => $paymentLink->currency_symbol,
                'country'         => $paymentLink->country,
                'type'            => $paymentLink->type,
                'token'           => $paymentLink->token,
                'title'           => $paymentLink->title,
                'image'           => $paymentLink->image,
                'details'         => $paymentLink->details,
                'limit'           => getAmount($paymentLink->limit,4),
                'min_amount'      => getAmount($paymentLink->min_amount,4),
                'max_amount'      => getAmount($paymentLink->max_amount,4),
                'price'           => getAmount($paymentLink->price,4),
                'qty'             => $paymentLink->qty,
                'status'          => $paymentLink->status,
                'string_status'   => $paymentLink->stringStatus->value,
                'created_at'      => $paymentLink->created_at,
        ];

        $data = [
            'base_url'      => url('/'),
            'default_image' => get_files_public_path('default'),
            'image_path'    => get_files_public_path('payment-link-image'),
            'currency_data' => Currency::active()->get(),
            'payment_link' => (object) $payment_link_data,
        ];
        $message =  ['success'=>[__('Payment Link Edit')]];
        return Helpers::success($data,$message);
    }
    /**
     * Payment link store
     *
     * @param Illuminate\Http\Request $request
     * @method POST
     * @return Illuminate\Http\Request
     */
    public function update(Request $request){

        $paymentLink = PaymentLink::auth()->find($request->target);

        if(empty($paymentLink)){
            $error = ['error'=>[__('Invalid Request!')]];
            return Helpers::error($error);
        }

        if($request->type == PaymentGatewayConst::LINK_TYPE_PAY){
            $validator = Validator::make($request->all(), [
                'currency'        => 'required',
                'currency_symbol' => 'required',
                'currency_name'   => 'required',
                'title'           => 'required|max:180',
                'type'            => 'required',
                'details'         => 'nullable',
                'limit'           => 'nullable',
                'min_amount'      => 'nullable|min:0.1',
                'max_amount'      => 'nullable|gt:min_amount',
                'image'           => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
            ]);

            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }

            $validated = $validator->validated();

            if($paymentLink->type == PaymentGatewayConst::LINK_TYPE_SUB){
                $validated['price'] = NULL;
                $validated['qty'] = NULL;
            }


            $validated = Arr::except($validated, ['image']);
            $validated['limit'] = $request->limit ? 1 : 2;
            $validated['user_id'] = Auth::id();

            try {

                if($request->hasFile('image')) {
                    try{
                        $image = upload_file($request->image,'payment-link-image', $paymentLink->image);
                        $upload_image = upload_files_from_path_dynamic([$image['dev_path']],'payment-link-image', $paymentLink->image);
                        delete_file($image['dev_path']);
                        $validated['image'] = $upload_image;
                    }catch(Exception $e) {
                        $error = ['error'=>[__("Something went wrong! Please try again.")]];
                        return Helpers::error($error);
                    }
                }

                $paymentLink->update($validated);

            } catch (\Exception $th) {
                $error = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }

        }else{
            $validator = Validator::make($request->all(), [
                'sub_currency'    => 'required',
                'currency_symbol' => 'required',
                'currency_name'   => 'required',
                'sub_title'       => 'required|max:180',
                'type'            => 'required',
                'price'           => 'nullable',
                'qty'             => 'nullable',
            ]);

            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }

            $validated = $validator->validated();
            $validated['currency'] = $validated['sub_currency'];
            $validated['title'] = $validated['sub_title'];
            $validated['user_id'] = Auth::id();

            if($paymentLink->type == PaymentGatewayConst::LINK_TYPE_PAY){

                $validated['image']      = NULL;
                $validated['details']    = NULL;
                $validated['limit']      = 2;
                $validated['min_amount'] = NULL;
                $validated['max_amount'] = NULL;

                $image_link = get_files_path('payment-link-image') . '/' . $paymentLink->image;
                delete_file($image_link);
            }

            $validated = Arr::except($validated, ['sub_currency','sub_title']);

            try {
                $paymentLink->update($validated);
            } catch (\Exception $th) {
                $error = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }
        }


        $data = [
            'payment_link' => $paymentLink,
        ];
        $message =  ['success'=>[__('Payment Link Updated Successful')]];
        return Helpers::success($data,$message);
    }
    /**
     * Payment link store
     *
     * @param Illuminate\Http\Request $request
     * @method POST
     * @return Illuminate\Http\Request
     */
    public function status(Request $request){


        $validator = Validator::make($request->all(), [
            'target'        => 'required',
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();
        $paymentLink = PaymentLink::auth()->find($validated['target']);
        if(empty($paymentLink)){
            $error = ['error'=>[__(__('Invalid Request,PayLink Data Not Found!'))]];
            return Helpers::error($error);
        }
        try {
            $status = $paymentLink->status == 1 ? 2 : 1;
            $paymentLink->update(['status' => $status]);

        } catch (\Exception $th) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        $message =  ['success'=>[__('Payment Link Status Updated Successful')]];
        return Helpers::onlysuccess($message);
    }

     //both user and merchant
    public function shareData(){
        $validator = Validator::make(request()->all(), [
            'token'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated =  $validator->validate();
        $payment_link = PaymentLink::with('user','merchant')->where('status', 1)->where('token', $validated['token'])->first();
         if(empty($payment_link)){
            $error = ['error'=>[__('Invalid Payment Link')]];
            return Helpers::error($error);
        }
        $payment_link->makeHidden(['stringStatus','shareLink','user','merchant','currency_info','user_id','merchant_id']);

        $payment_gateways = PaymentGateway::active()->addMoney()->whereNotIn('alias',['tatum','authorize'])->automatic()->get();
        $payment_gateways->makeHidden(['credentials','supported_currencies','desc','input_fields','status','last_edit_by','env','currencies']);

        $payment_settings = GatewayAPi::first();
        if(empty($payment_settings)){
            $error = ['error'=>[__('Can Not Payment Now, Please Contact Support')]];
            return Helpers::error($error);
        }
        $payment_settings->makeHidden(['admin_id','secret_key','account_id','created_at','updated_at']);

        $active_currencies = Currency::orderByDesc('default')->active()->get();
        $active_currencies->makeHidden(['admin_id','sender','receiver','default','status','created_at','updated_at','both','senderCurrency','receiverCurrency','editData','currencyImage']);
        $payment_link_type = [PaymentGatewayConst::LINK_TYPE_PAY,PaymentGatewayConst::LINK_TYPE_SUB];


        $data = [
            'base_curr'                 => get_default_currency_code(),
            'base_curr_symbol'          => get_default_currency_symbol(),
            'default_image_path'        => files_asset_path_basename('default'),
            'payment_link_image_path'   => files_asset_path_basename('payment-link-image'),
            'gateway_image_path'        => files_asset_path_basename('payment-gateways'),
            'wallet_image_path'         => files_asset_path_basename('currency-flag'),
            'payment_link_type'         => $payment_link_type,
            'payment_link'              => $payment_link,
            'payment_gateways'          => $payment_gateways,
            'payment_link'              => $payment_link,
            'payment_settings'          => $payment_settings,
            'active_currencies'         => $active_currencies,
        ];

        $message = ['success' => [__('Shareable Link Information')]];
        return Helpers::success($data, $message);

    }

    public function paymentLinkSubmit(Request $request){

        $validator = Validator::make($request->all(), [
            'target'          => 'required',
            'payment_type'    => 'required|string'
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        $payment_link = PaymentLink::with('user','merchant')->where('status', 1)->where('id', $validated['target'])->first();
         if(empty($payment_link)){
            $error = ['error'=>[__('Invalid Payment Link')]];
            return Helpers::error($error);
        }

        $payment_method = [
            PaymentGatewayConst::TYPE_GATEWAY_PAYMENT   => 'gatewayPaymentRequest',
            PaymentGatewayConst::TYPE_WALLET_SYSTEM     => 'walletPaymentRequest',
            PaymentGatewayConst::TYPE_CARD_PAYMENT      => 'cardPaymentRequest',
        ];


        if(!array_key_exists($validated['payment_type'], $payment_method)) return abort(404);
        $method = $payment_method[$validated['payment_type']];
        return $this->$method($request);
    }

    /**
     * Wallet Payment Request
     *
     * @method POST
     * @return Illuminate\Http\Request
     */
    public function walletPaymentRequest(Request $request){


         $validator = Validator::make($request->all(), [
            'target'            => 'required',
            'email'             => 'required|email',
            'phone'             => 'required|numeric',
            'full_name'         => 'required|string',
            'user_id'           => 'required|string',
            'wallet_currency'   => 'required|string',
            'amount'            => 'required|gt:0',
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();


        $payment_link = PaymentLink::with('user','merchant')->find($validated['target']);

        if(!empty($payment_link->price)){
            $amount = $payment_link->price * $payment_link->qty;
            if($validated['amount'] != $amount){
                $error = ['error'=>[__('Please Enter A Valid Amount')]];
                return Helpers::error($error);
            }
        }else{
            if($payment_link->limit == 1){
                if($validated['amount'] < $payment_link->min_amount || $validated['amount'] > $payment_link->max_amount){
                    $error = ['error'=>[__("Please follow the transaction limit")]];
                    return Helpers::error($error);

                }else{
                    $amount = $validated['amount'];
                }
            }else{
                $amount = $validated['amount'];
            }
        }
        $validated['payment_link'] = $payment_link;

        $receiver_currency = Currency::where('code', $validated['payment_link']->currency)->first();


        if(empty($receiver_currency)){
            $error = ['error'=>[__('Receiver currency not found!')]];
            return Helpers::error($error);
        }
        if($payment_link->user_id != null){
            $receiver_wallet = UserWallet::with('user','currency')->where('user_id', $payment_link->user_id)->whereHas("currency",function($q) use ($payment_link) {
                $q->where("code",$payment_link->currency)->active();
            })->active()->first();
            $userType = "USER";
            $relation_name = 'user';
            $user_guard = "api";
        }elseif($payment_link->merchant_id != null){
            $receiver_wallet = MerchantWallet::with('merchant','currency')->where('merchant_id', $payment_link->merchant_id)->whereHas("currency",function($q) use ($payment_link) {
                $q->where("code",$payment_link->currency)->active();
            })->active()->first();
            $userType = "MERCHANT";
            $relation_name ="merchant";
            $user_guard = "merchant_api";
        }

        if(empty($receiver_wallet)){
            $error = ['error'=>[__('Receiver wallet not found')]];
            return Helpers::error($error);
        }
        $sender = User::where('id',$validated['user_id'])->first();

        if(!$sender){
            $error = ['error'=>[__('Sender Not Found')]];
            return Helpers::error($error);
        }

        if($userType === "USER" && $sender->email ===  $receiver_wallet->$relation_name->email){
            $error = ['error'=>[__('Can not pay to your own payment link')]];
            return Helpers::error($error);

        }

        $sender_wallet = UserWallet::where('user_id', $sender->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['wallet_currency'])->active();
        })->active()->first();
        if(!$sender_wallet){
            $error = ['error'=>[__('Sender Wallet Not Found')]];
            return Helpers::error($error);
        }

        $sender_currency =   $sender_wallet->currency;


        $validated['receiver']          = $receiver_wallet->$relation_name??'user';
        $validated['receiver_wallet']   = $receiver_wallet;
        $validated['sender']            = $sender;
        $validated['sender_wallet']     = $sender_wallet;
        $validated['sender_currency']   = $sender_currency;
        $validated['transaction_type']  = PaymentGatewayConst::TYPEPAYLINK;

        $payment_link_charge = TransactionSetting::where('slug', PaymentGatewayConst::paylink_slug())->where('status',1)->first();

        //sender charge calculation
        $exchange_rate =  $sender_wallet->currency->rate/$receiver_currency->rate;
        $sender_fixed_charge        = $payment_link_charge->fixed_charge * $exchange_rate;
        $sender_percent_charge      = ($amount / 100) * $payment_link_charge->percent_charge;
        $sender_total_charge        = $sender_fixed_charge + $sender_percent_charge;
        $sender_amount              = ($exchange_rate * $amount );
        $sender_payable             = (($exchange_rate * $amount ) + $sender_total_charge);

        if($sender_payable > $sender_wallet->balance ){
            $error = ['error'=>[__('Your wallet balance is insufficient to complete this transaction, Please add funds to proceed.')]];
            return Helpers::error($error);
        }

        //receiver & admin charge calculation
        $receiver_fixed_charge          = $payment_link_charge->fixed_charge * $receiver_currency->rate;
        $receiver_percent_charge        = ($amount / 100) * $payment_link_charge->percent_charge;
        $receiver_total_charge          = $receiver_fixed_charge + $receiver_percent_charge;
        $receiver_will_get              = ($amount - $receiver_total_charge);
        $base_cur_amount                = ($amount/$receiver_currency->rate);

        if($receiver_will_get <= 0 ){
            $error = ['error'=>[__('Transaction Failed, Please Contact With Support!')]];
            return Helpers::error($error);
        }


        $charge_calculation = [
            'requested_amount'          => $amount,
            'request_amount_admin'      => $base_cur_amount,
            //pay-link owner
            'r_exchange_rate'           => $receiver_currency->rate,
            'receiver_currency_code'    => $receiver_currency->code,
            'receiver_amount'           => $receiver_will_get,
            'r_fixed_charge'            => $receiver_fixed_charge,
            'r_percent_charge'          => $receiver_percent_charge,
            'r_total_charge'            => $receiver_total_charge,
            //pay-link payment user
            's_exchange_rate'           => $exchange_rate,
            'sender_cur_code'           => $sender_currency->code,
            'sender_amount'             => $sender_amount,
            's_fixed_charge'            => $sender_fixed_charge,
            's_percent_charge'          => $sender_percent_charge,
            's_total_charge'            => $sender_total_charge,
            'sender_payable'            => $sender_amount,
            //admin
            'base_currency_code'        => get_default_currency_code(),
            'base_currency_rate'        => get_default_currency_rate(),
        ];



        $validated['charge_calculation'] = $charge_calculation;
        $validated['user_type'] = $userType??"";
        $validated['user_guard'] = $user_guard??"";
        $validated['payment_type'] = PaymentGatewayConst::TYPE_WALLET_SYSTEM;

        try {
            $this->createWalletTransactionPayLink($validated,PaymentGatewayConst::STATUSSUCCESS);

            $data = [
                'token'     => $payment_link->token
            ];

            $message = ['success' => [__('Transaction Successful')]];
            return Helpers::success($data, $message);
       } catch (Exception $e) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
       }

    }

     /**
     * Card Payment Request
     *
     * @method POST
     * @return Illuminate\Http\Request
     */

    public function cardPaymentRequest(Request $request){

        $validator = Validator::make($request->all(), [
            'target'     => 'required',
            'email'      => 'required|email',
            'phone'      => 'nullable',
            'card_name'  => 'required',
            'token'      => 'required',
            'last4_card' => 'required',
            'amount'     => 'required|gt:0',
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        $credentials = GatewayAPi::first();
        if(empty($credentials)){
            $error = ['error'=>[__('Can Not Payment Now, Please Contact Support')]];
            return Helpers::error($error);
        }
        $credentials->makeHidden(['admin_id','id','account_id','created_at','updated_at','payment_gateway_status','card_status','wallet_status']);


        $payment_link = PaymentLink::with('user','merchant')->find($validated['target']);

        if(!empty($payment_link->price)){
            $amount = $payment_link->price * $payment_link->qty;
            if($validated['amount'] != $amount){
                $error = ['error'=>[__('Please Enter A Valid Amount')]];
                return Helpers::error($error);
            }
        }else{
            if($payment_link->limit == 1){
                if($validated['amount'] < $payment_link->min_amount || $validated['amount'] > $payment_link->max_amount){
                    $error = ['error'=>[__("Please follow the transaction limit")]];
                    return Helpers::error($error);
                }else{
                    $amount = $validated['amount'];
                }
            }else{
                $amount = $validated['amount'];
            }
        }
        $validated['payment_link'] = $payment_link;
        $receiver_currency = Currency::where('code', $validated['payment_link']->currency)->first();
        if(empty($receiver_currency)){
            $error = ['error'=>[__('Receiver currency not found!')]];
            return Helpers::error($error);
        }
        if($payment_link->user_id != null){
            $receiver_wallet = UserWallet::with('user','currency')->where('user_id', $payment_link->user_id)->whereHas("currency",function($q) use ($payment_link) {
                $q->where("code",$payment_link->currency)->active();
            })->active()->first();
            $userType = "USER";
            $user_guard = "api";
        }elseif($payment_link->merchant_id != null){
            $receiver_wallet = MerchantWallet::with('merchant','currency')->where('merchant_id', $payment_link->merchant_id)->whereHas("currency",function($q) use ($payment_link) {
                $q->where("code",$payment_link->currency)->active();
            })->active()->first();
            $userType = "MERCHANT";
            $user_guard = "merchant_api";
        }


        if(empty($receiver_wallet)){
            $error = ['error'=>[__('Receiver wallet not found')]];
            return Helpers::error($error);
        }

        $sender_currency = Currency::where('code', $payment_link->currency)->where('name', $payment_link->currency_name)->first();

        $validated['receiver_wallet'] = $receiver_wallet;
        $validated['sender_currency'] = $sender_currency;
        $validated['transaction_type'] = PaymentGatewayConst::TYPEPAYLINK;

        $payment_link_charge = TransactionSetting::where('slug', PaymentGatewayConst::paylink_slug())->where('status',1)->first();

         //receiver & admin charge calculation
         $receiver_fixed_charge          = $payment_link_charge->fixed_charge * $receiver_currency->rate;
         $receiver_percent_charge        = ($amount / 100) * $payment_link_charge->percent_charge;
         $receiver_total_charge          = $receiver_fixed_charge + $receiver_percent_charge;
         $receiver_will_get              = ($amount - $receiver_total_charge);
         $sender_payable                 = ($amount + $receiver_total_charge);
         $base_cur_amount                = ($amount/$receiver_currency->rate);

        if($receiver_will_get <= 0 ){
            $error = ['error'=>[__('Transaction Failed, Please Contact With Support!')]];
            return Helpers::error($error);
        }

        $charge_calculation = [
            'requested_amount'          => $amount,
            'request_amount_admin'      => $base_cur_amount,
             //pay-link owner
             'r_exchange_rate'           => $receiver_currency->rate,
             'receiver_currency_code'    => $receiver_currency->code,
             'receiver_amount'           => $receiver_will_get,
             'r_fixed_charge'            => $receiver_fixed_charge,
             'r_percent_charge'          => $receiver_percent_charge,
             'r_total_charge'            => $receiver_total_charge,

            'sender_cur_code'           => $sender_currency->code,
            'sender_payable'            => $sender_payable,

            'base_currency_code'        => get_default_currency_code(),
            'base_currency_rate'        => get_default_currency_rate(),
        ];

        $validated['charge_calculation'] = $charge_calculation;
        $validated['userType'] = $userType??"";
        $validated['user_guard'] = $user_guard??"";

       try {
            $this->stripeLinkInit($validated, $credentials);

            $data = [
                'token'     => $payment_link->token
            ];

            $message = ['success' => [__('Transaction Successful')]];
            return Helpers::success($data, $message);
       }catch (Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return Helpers::error($error);
       }

    }

    /**
     * Gateway Payment Request
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function gatewayPaymentRequest(Request $request){

        $validator = Validator::make($request->all(), [
            'target'             => 'required',
            'amount'             => 'required',
            'email'              => 'required|email',
            'full_name'          => 'required|string',
            'phone'              => 'required|numeric',
            'payment_gateway'    => 'required|exists:payment_gateways,alias',
            'source'             => 'required|string|in:'.GlobalConst::WEB.','.GlobalConst::APP,
            'success_return_url' => 'nullable|url',
            'cancel_return_url'  => 'nullable|url',
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        $payment_link = PaymentLink::with('user','merchant')->find($validated['target']);
        if(!empty($payment_link->price)){
            $amount = $payment_link->price * $payment_link->qty;
            if($validated['amount'] != $amount){
                $error = ['error'=>[__('Please Enter A Valid Amount')]];
                return Helpers::error($error);
            }
        }else{
            if($payment_link->limit == 1){
                if($validated['amount'] < $payment_link->min_amount || $validated['amount'] > $payment_link->max_amount){
                    $error = ['error'=>[__("Please follow the transaction limit")]];
                    return Helpers::error($error);
                }else{
                    $amount = $validated['amount'];
                }
            }else{
                $amount = $validated['amount'];
            }
        }
        $validated['payment_link'] = $payment_link;
        if(empty($payment_link)){
            $error = ['error'=>[__('Invalid Request!')]];
            return Helpers::error($error);
        }

        $payment_gateway = PaymentGateway::where('alias', $validated['payment_gateway'])->withWhereHas('currency',function($q) use ($payment_link){
            $q->where("currency_code",$payment_link->currency);
        })->first();

        if(!$payment_gateway){
            $error = ['error'=>[__('Gateway Currency Is Not Supported!')]];
            return Helpers::error($error);
        }

        $request->merge(['currency' => $payment_gateway->currency->alias]);

       try {
            $instance = PayLinkPaymentGateway::init($request->all())->type(PaymentGatewayConst::TYPEPAYLINK)->payLinkGateway()->render();
       }catch (\Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return Helpers::error($error);
       }

       return $instance;
    }

    /**
     * Stripe Success
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function stripeSuccess($trx){

        $token = $trx;
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::STRIPE)->where("identifier",$token)->first();
        $source      = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->platform_source->success_return_url ?? '';
        $cancel_url  = $checkTempData->data->platform_source->cancel_return_url ?? '';

        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();
        try{
            PayLinkPaymentGateway::init($checkTempData)->type(PaymentGatewayConst::TYPEPAYLINK)->responseReceive('stripe');
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        $payment_link = PaymentLink::find($checkTempData['data']->validated->target);
        $token = $payment_link->token;

        if ($source == GlobalConst::WEB) {
            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Payment Successful, Please Go Back Your App')]]
            );
        }

    }

    /**
     * Paypal Success
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function paypalSuccess(Request $request, $gateway){
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";
        $checkTempData = TemporaryData::where("type",$gateway)->where("identifier",$token)->first();

        $source      = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->platform_source->success_return_url ?? '';
        $cancel_url  = $checkTempData->data->platform_source->cancel_return_url ?? '';


        if(!$checkTempData){
             return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();

        try{
            PayLinkPaymentGateway::init($checkTempData)->type(PaymentGatewayConst::TYPEPAYLINK)->responseReceive();
        }catch(Exception $e) {
             return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $payment_link = PaymentLink::find($checkTempData['data']->validated->target);
        $token = $payment_link->token;

        if ($source == GlobalConst::WEB) {
            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Payment Successful, Please Go Back Your App')]],
            );
        }

    }
    /**
     * Paypal Canceled
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function paypalCancel(Request $request, $gateway) {
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";

        $checkTempData = TemporaryData::where("type",$gateway)->where("identifier",$token)->first();
        $source      = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $cancel_url  = $checkTempData->data->platform_source->cancel_return_url ?? '';

        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $payment_link = PaymentLink::find($checkTempData['data']->validated->target);
        if(!$payment_link){
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        if( $token){
            TemporaryData::where("identifier",$token)->delete();
        }

        $token = $payment_link->token;
        if ($source == GlobalConst::WEB) {
            return redirect()->away($cancel_url . (parse_url($cancel_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Transaction Canceled Successfully')]],
            );
        }

    }

    /**
     * Flutter-Wave Callback
     *
     * @method GET
     * @return Illuminate\Http\Request
     */
    public function flutterwaveCallback()
    {
        $status = request()->status;
        $transactionID = Flutterwave::getTransactionIDFromCallback();
        $data = Flutterwave::verifyTransaction($transactionID);
        $token = request()->tx_ref;
        $checkTempData = TemporaryData::where("type",'flutterwave')->where("identifier",$token)->first();

        $source      = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->platform_source->success_return_url ?? '';
        $cancel_url  = $checkTempData->data->platform_source->cancel_return_url ?? '';


        //if payment is successful
        if ($status ==  'successful' || $status == 'completed') {

            if(!$checkTempData){
                 return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
            }
            $checkTempData = $checkTempData->toArray();
            try{
                PayLinkPaymentGateway::init($checkTempData)->type(PaymentGatewayConst::TYPEPAYLINK)->responseReceive('flutterwave');
            }catch(Exception $e){
                return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
            }
            $payment_link = PaymentLink::find($checkTempData['data']->validated->target);
            $token = $payment_link->token;

            if ($source == GlobalConst::WEB) {
                return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
            } else {
                return Helpers::success(
                    ['token' => $token],
                    ['success' => [__('Payment Successful, Please Go Back Your App')]],
                );
            }
        }
        elseif ($status ==  'cancelled'){
            return $source == GlobalConst::WEB
                ? redirect()->away($cancel_url)
                : Helpers::onlysuccess(['success' => [__('Transaction Canceled Successfully')]]);
        }
        else{
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }

    public function successGlobal(Request $request, $gateway){

        try{
            $token = PayLinkPaymentGateway::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            $source      = $temp_data->data->platform_source->source ?? GlobalConst::APP;
            $success_url = $temp_data->data->platform_source->success_return_url ?? '';
            $cancel_url  = $temp_data->data->platform_source->cancel_return_url ?? '';

             if(!$temp_data) {
                if(Transaction::where('callback_ref',$token)->exists()) {
                    $payment_link = PaymentLink::find($temp_data->data->validated->target);
                    $token = $payment_link->token;
                    if ($source == GlobalConst::WEB) {
                            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
                    } else {
                        return Helpers::success(
                            ['token' => $token],
                            ['success' => [__('Payment Successful, Please Go Back Your App')]]
                        );
                    }
                }else {
                   return   $source == GlobalConst::WEB
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
            $instance = PayLinkPaymentGateway::init($temp_data)->type(PaymentGatewayConst::TYPEPAYLINK)->responseReceive($temp_data['type']);
            if($instance instanceof RedirectResponse) return $instance;
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $payment_link = PaymentLink::find($temp_data['data']->validated->target);
        $token = $payment_link->token;

        if ($source == GlobalConst::WEB) {
            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Payment Successful, Please Go Back Your App')]]
            );
        }
    }

    public function cancelGlobal(Request $request, $gateway) {
        $token = PayLinkPaymentGateway::getToken($request->all(),$gateway);
        $temp_data = TemporaryData::where("identifier",$token)->first();

        $source      = $temp_data->data->platform_source->source ?? GlobalConst::APP;
        $cancel_url  = $temp_data->data->platform_source->cancel_return_url ?? '';

        if(!$temp_data){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }

        $payment_link = PaymentLink::find($temp_data['data']->validated->target);

        if(!$payment_link){
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }

        if( $token){
            $temp_data->delete();
        }


        $token = $payment_link->token;
        if ($source == GlobalConst::WEB) {
            return redirect()->away($cancel_url . (parse_url($cancel_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Transaction Canceled Successfully')]],
            );
        }
    }

    public function postSuccess(Request $request, $gateway)
    {

        try{
            $token      = PayLinkPaymentGateway::getToken($request->all(),$gateway);
            $temp_data  = TemporaryData::where("identifier",$token)->first();
            $source     = $temp_data->data->platform_source->source ?? GlobalConst::APP;
            $cancel_url = $temp_data->data->platform_source->cancel_return_url ?? '';
        }catch(Exception $e) {
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        return $this->successGlobal($request, $gateway);
    }

    public function postCancel(Request $request, $gateway)
    {
        try{
            $token      = PayLinkPaymentGateway::getToken($request->all(),$gateway);
            $temp_data  = TemporaryData::where("identifier",$token)->first();
            $source     = $temp_data->data->platform_source->source ?? GlobalConst::APP;
            $cancel_url = $temp_data->data->platform_source->cancel_return_url ?? '';
        }catch(Exception $e) {
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        return $this->cancelGlobal($request, $gateway);
    }

     /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function callback(Request $request,$gateway){
        $callback_token = $request->get('token');
        $callback_data = $request->all();
        try{
            PayLinkPaymentGateway::init([])->type(PaymentGatewayConst::TYPEPAYLINK)->handleCallback($callback_token,$callback_data,$gateway);
        }catch(Exception $e) {
            // handle Error
            // logger($e);
        }
    }
    /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function redirectBtnPay(Request $request, $gateway){
        try{
            return PayLinkPaymentGateway::init([])->type(PaymentGatewayConst::TYPEPAYLINK)->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return Helpers::error($error);
        }
    }


    /**
     * SLL-COMMERZ Callback
     *
     * @method POST
     * @return Illuminate\Http\Request
     */

    //sslcommerz success
    public function sllCommerzSuccess(Request $request){

        $data          = $request->all();
        $token         = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();

        $source      = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $success_url = $checkTempData->data->platform_source->success_return_url ?? '';
        $cancel_url  = $checkTempData->data->platform_source->cancel_return_url ?? '';
        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);

        }
        $checkTempData = $checkTempData->toArray();
        if( $data['status'] != "VALID"){
            return $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__('Transaction failed')]]);
        }
        try{
            PayLinkPaymentGateway::init($checkTempData)->type(PaymentGatewayConst::TYPEPAYLINK)->responseReceive('sslcommerz');
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $payment_link = PaymentLink::find($checkTempData['data']->validated->target);

        $token = $payment_link->token;

        if ($source == GlobalConst::WEB) {
            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Payment Successful, Please Go Back Your App')]]
            );
        }
    }
    //sslCommerz fails
    public function sllCommerzFails(Request $request){
        $data          = $request->all();
        $token         = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        $source        = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $cancel_url    = $checkTempData->data->platform_source->cancel_return_url ?? '';
        if(!$checkTempData){
            return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();
        if( $data['status'] == "FAILED"){
            TemporaryData::destroy($checkTempData['id']);
            return $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__('Transaction failed')]]);
        }

    }
    //sslCommerz canceled
    public function sllCommerzCancel(Request $request){
        $data          = $request->all();
        $token         = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        $source        = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
        $cancel_url    = $checkTempData->data->platform_source->cancel_return_url ?? '';
        if(!$checkTempData){
             return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
        }
        $checkTempData = $checkTempData->toArray();
        if( $data['status'] != "VALID"){
            TemporaryData::destroy($checkTempData['id']);
             return $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__('Transaction failed')]]);;
        }
    }

    //coingate response start
    public function coinGateSuccess(Request $request, $gateway){
        try{
            $token = $request->token;
            $checkTempData = TemporaryData::where("type",PaymentGatewayConst::COINGATE)->where("identifier",$token)->first();
            $source      = $checkTempData->data->platform_source->source ?? GlobalConst::APP;
            $success_url = $checkTempData->data->platform_source->success_return_url ?? '';
            $cancel_url  = $checkTempData->data->platform_source->cancel_return_url ?? '';
            if(!$checkTempData){

                $error = ['error'=>[__('Transaction Failed. Record didn\'t saved properly. Please try again.')]];
                return Helpers::error($error);
            }

            if(!$checkTempData) {
                if(Transaction::where('callback_ref',$token)->exists()) {
                    $payment_link = PaymentLink::find($checkTempData->data->validated->target);
                    $token = $payment_link->token;
                    if ($source == GlobalConst::WEB) {
                            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
                    } else {
                        return Helpers::success(
                            ['token' => $token],
                            ['success' => [__('Payment Successful, Please Go Back Your App')]]
                        );
                    }
                }else {
                   return   $source == GlobalConst::WEB
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
            PayLinkPaymentGateway::init($temp_data)->type(PaymentGatewayConst::TYPEPAYLINK)->responseReceive('coingate');
        }catch(Exception $e) {
            return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
        }


        $payment_link = PaymentLink::find($temp_data['data']->validated->target);
        $token = $payment_link->token;

        if ($source == GlobalConst::WEB) {
            return redirect()->away($success_url . (parse_url($success_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
        } else {
            return Helpers::success(
                ['token' => $token],
                ['success' => [__('Payment Successful, Please Go Back Your App')]]
            );
        }
    }
    public function coinGateCancel(Request $request, $gateway){
        if($request->has('token')) {
            $identifier = $request->token;
            $temp_data = TemporaryData::where('identifier', $identifier)->first();
            $source      = $temp_data->data->platform_source->source ?? GlobalConst::APP;
            $cancel_url  = $temp_data->data->platform_source->cancel_return_url ?? '';
            if(!$temp_data){
                 return  $source == GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Transaction Failed. Record didn't save properly. Please try again.")]]);
            }
                // $temp_data->delete();

            $payment_link = PaymentLink::find($temp_data['data']->validated->target);

            if(!$payment_link){
                return $source ==  GlobalConst::WEB
                    ? redirect()->away($cancel_url)
                    : Helpers::error(['error' => [__("Something went wrong! Please try again.")]]);
            }

            if( $identifier){
                $temp_data->delete();
            }


            $token = $payment_link->token;
            if ($source == GlobalConst::WEB) {
                return redirect()->away($cancel_url . (parse_url($cancel_url, PHP_URL_QUERY) ? '&' : '?') . 'token=' . $token);
            } else {
                return Helpers::success(
                    ['token' => $token],
                    ['success' => [__('Transaction Canceled Successfully')]],
                );
            }

        }
    }

    /**
         * redirectUsingHTMLForm
         *
         * @method POST
         * @return Illuminate\Http\Request
     */
    public function redirectUsingHTMLForm(Request $request, $gateway)
    {
        $temp_data = TemporaryData::where('identifier', $request->token)->first();
        if(!$temp_data || $temp_data->data->action_type != PaymentGatewayConst::REDIRECT_USING_HTML_FORM){
            $error = ['error'=>[__('Request token is invalid!')]];
            return Helpers::error($error);

        }
        $redirect_form_data = $temp_data->data->redirect_form_data;
        $action_url         = $temp_data->data->action_url;
        $form_method        = $temp_data->data->form_method;

        return view('payment-gateway.redirect-form', compact('redirect_form_data', 'action_url', 'form_method'));
    }


}
