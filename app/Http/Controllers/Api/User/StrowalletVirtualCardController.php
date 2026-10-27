<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\GlobalConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use App\Http\Helpers\TransactionLimit;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\StrowalletCustomerKyc;
use App\Models\StrowalletVirtualCard;
use App\Models\StrowalletWebhookData;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Models\VirtualCardApi;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\VirtualCard\CreateMail;
use App\Notifications\User\VirtualCard\Fund;
use App\Notifications\User\VirtualCard\Withdraw;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class  StrowalletVirtualCardController extends Controller
{
    protected $api;
    protected $api_mode;
    protected $card_limit;
    protected $basic_settings;
    public function __construct()
    {
        $cardApi              = VirtualCardApi::first();
        $this->api            = $cardApi;
        $this->api_mode       = $cardApi->config->strowallet_mode;
        $this->card_limit     = $cardApi->card_limit;
        $this->basic_settings = BasicSettingsProvider::get();
    }

    public function index()
    {
        $user = auth()->user();
        $basic_settings = BasicSettings::first();
        $card_basic_info = [
            'card_create_limit' => @$this->api->card_limit,
            'card_back_details' => @$this->api->card_details,
            'card_bg'           => get_image(@$this->api->image,'card-api'),
            'site_title'        => @$basic_settings->site_name,
            'site_logo'         => get_logo(@$basic_settings,'dark'),
            'site_fav'          => get_fav($basic_settings,'dark'),
        ];
        $myCards = StrowalletVirtualCard::where('user_id',$user->id)->latest()->limit($this->card_limit)->get()->map(function($data){

            $basic_settings = BasicSettings::first();
            $authCode        = $data->authorization_code;
            $authCodeSentAt  = $data->auth_code_send_at ? \Carbon\Carbon::parse($data->auth_code_send_at) : null;
            $authCodeExpired = !$authCodeSentAt || $authCodeSentAt->diffInMinutes(now()) >= 5;

            if(is_null($authCode)){
                $authorization_code = __("No OTP");
            }elseif($authCodeExpired){
                $authorization_code = __("OTP Expired");
            }else{
                $authorization_code = $authCode;
            }


            return[
                'id'                 => $data->id,
                'name'               => $data->name_on_card,
                'card_id'            => $data->card_id,
                'card_status'        => $data->card_status,
                'card_number'        => $data->card_number ?? '---- ---- ---- ----',
                'card_brand'         => $data->card_brand,
                'card_user_id'       => $data->card_user_id,
                'expiry'             => $data->expiry,
                'cvv'                => $data->cvv ?? "***",
                'card_type'          => ucwords($data->card_type),
                'amount'             => getAmount($data->balance,2),
                'currency'           => $data->currency,
                'authorization_code' => $authorization_code,
                'auth_code_send_at'  => $data->auth_code_send_at ?? null,
                'card_back_details'  => @$this->api->card_details,
                'card_bg'            => get_image(@$this->api->image,'card-api'),
                'site_title'         => @$basic_settings->site_name,
                'site_logo'          => get_logo(@$basic_settings,'dark'),
                'status'             => $data->is_active,
                'is_default'         => $data->is_default,
            ];
        });
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->get()->map(function($data){
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
        $transactions = Transaction::auth()->virtualCard()->latest()->get()->map(function($item){
            $statusInfo = [
                "success" =>      1,
                "pending" =>      2,
                "rejected" =>     3,
                ];

            return[
                'id'               => $item->id,
                'trx'              => $item->trx_id,
                'transaction_type' => "Virtual Card".'('. @$item->remark.')',
                'request_amount'   => get_amount($item->request_amount,$item->details->charges->card_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                'payable'          => get_amount($item->payable,$item->details->charges->from_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                'exchange_rate'    => get_amount(1,$item->details->charges->card_currency??get_default_currency_code()) ." = ". get_amount($item->details->charges->exchange_rate??1,$item->details->charges->from_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                'total_charge'     => get_amount($item->charge->total_charge,$item->details->charges->from_currency??get_default_currency_code()),
                'card_amount'      => get_amount(@$item->details->card_info->amount??@$item->details->card_info->balance,$item->details->charges->card_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                'card_number'      => $item->details->card_info->card_pan??$item->details->card_info->maskedPan??$item->details->card_info->card_number??"---- ---- ---- ----",
                'current_balance'  => get_amount($item->available_balance,$item->details->charges->from_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                'status'           => $item->stringStatus->value,
                'status_value'     => $item->status,
                'date_time'        => $item->created_at,
                'status_info'      => (object)$statusInfo,

            ];
        });

        $customer_card = StrowalletVirtualCard::where('user_id',$user->id)->count();
        $customer_kyc  = StrowalletCustomerKyc::where('user_id',$user->id)->first();

        $supported_currency = support_currencies(['USD']);
        $get_remaining_fields = [
            'transaction_type'  =>  PaymentGatewayConst::VIRTUALCARD,
            'attribute'         =>  PaymentGatewayConst::RECEIVED,
        ];

        $data =[
            'base_curr'                => get_default_currency_code(),
            'base_curr_rate'           => get_amount(get_default_currency_rate(),null,get_wallet_precision()),
            'get_remaining_fields'     => (object) $get_remaining_fields,
            'supported_currency'       => $supported_currency,
            'card_create_action'       => $customer_card <  $this->card_limit ? true : false,
            'strowallet_customer_info' => $customer_kyc != null ? true : false,
            'card_basic_info'          => (object) $card_basic_info,
            'myCards'                  => $myCards,
            'user'                     => $user,
            'cardCharge'               => (object)$cardCharge,
            'transactions'             => $transactions,
        ];
        $message =  ['success'=>[__('Virtual Card')]];
        return Helpers::success($data,$message);
    }

    public function createCustomer(){
        $user = auth()->user();
        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        if(!$customer_kyc){
            $error = ['error'=>[__("Card Holder Not Created Yet. Please create a card holder first.")]];
            return Helpers::error($error);
        }

        $data=[
            'card_holder' =>  $customer_kyc,
            'options'     => [
                'id_type'           => ['national_id','passport','drivers_license'],
                'account_purpose'   => StrowalletCustomerKyc::ACCOUNT_PURPOSES,
                'employment_status' => StrowalletCustomerKyc::EMPLOYMENT_STATUSES,
                'occupation'        => StrowalletCustomerKyc::OCCUPATIONS,
            ],
        ];
        $message =  ['success'=>[__('Card Holder Information')]];
        return Helpers::success($data,$message);

    }

    public function storeCustomer(Request $request){

        $user         = auth()->user();
        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();

        if($customer_kyc && !empty($customer_kyc->customer_id)){
            $error = ['error'=>[__('Card holder information can not be changed after it is verified.')]];
            return Helpers::error($error);
        }

        if(!$customer_kyc || $customer_kyc->id_image == null){
            $id_image_validate_rules = "required|image|mimes:jpg,png,svg,webp|max:10240";
        }else{
            $id_image_validate_rules = "nullable|image|mimes:jpg,png,svg,webp|max:10240";
        }

        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|min:2|max:100',
            'first_name'        => 'required|string|min:2|max:50',
            'last_name'         => 'required|string|min:2|max:50',
            'dob'               => 'required|date|before:today',
            'id_type'           => 'required|string|in:national_id,passport,drivers_license',
            'id_number'         => 'required|string|min:3|max:50',
            'email'             => 'required|email|max:100',
            'line1'             => 'required|string|max:250',
            'city'              => 'required|string|max:100',
            'state'             => 'required|string|max:100',
            'postal_code'       => 'required|string|max:20',
            'country'           => 'required|string',
            'phone_code'        => 'required|string|max:6',
            'phone'             => 'required|string|max:20',
            'account_purpose'   => 'required|string|in:'.implode(',',array_keys(StrowalletCustomerKyc::ACCOUNT_PURPOSES)),
            'employment_status' => 'required|string|in:'.implode(',',array_keys(StrowalletCustomerKyc::EMPLOYMENT_STATUSES)),
            'occupation'        => 'required|string|in:'.implode(',',array_keys(StrowalletCustomerKyc::OCCUPATIONS)),
            'id_image'          => $id_image_validate_rules,
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated               = $validator->validate();
        $validated['phone_code'] = remove_special_char($validated['phone_code']);

        $old_image = $customer_kyc->id_image ?? null;
        $new_image = null;

        if($request->hasFile("id_image")) {
            $image = upload_file($validated['id_image'],'junk-files');
            // Keep the old file until the API call succeeds, so a rollback leaves it intact
            $new_image = upload_files_from_path_dynamic([$image['dev_path']],'card-kyc-images');
            delete_file($image['dev_path']);
            $validated['id_image'] = $new_image;
        }

        DB::beginTransaction();
        try{
            // Save to DB + storage first, then send the stored image's public URL to Strowallet
            $kyc = StrowalletCustomerKyc::updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );
            $id_front_image = $kyc->idImageData;

            $api_customer = create_stowallet_cardkyc($validated,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$id_front_image,$this->api_mode);

            if(!isset($api_customer['status']) || $api_customer['status'] == false){
                DB::rollBack();
                $error = ['error'=>(array) parse_error($api_customer, __("Something went wrong! Please try again."))];
                return Helpers::error($error);
            }

            $kyc->update([
                'reference'       => $api_customer['data']['reference'] ?? null,
                'customer_id'     => $api_customer['data']['customer_id'] ?? null,
                'kyc_status'      => $api_customer['data']['status'] ?? null,
                'completion_link' => $api_customer['data']['completion_link'] ?? null,
                'mode'            => $api_customer['data']['mode'] ?? null,
            ]);

            DB::commit();

            // API succeeded, so the replaced image is no longer needed
            if($new_image && $old_image){
                delete_file(get_files_path('card-kyc-images') . '/' . $old_image);
            }

            $message =  ['success'=>[__('Customer has been created successfully.')]];
            return Helpers::onlysuccess($message);

        }catch(Exception $e){
            DB::rollBack();
            $error = ['error'=>[$e->getMessage() ?: __("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }

    //charge
    public function charges(){
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->get()->map(function($data){
            return [
                'id'                => $data->id,
                'slug'              => $data->slug,
                'title'             => $data->title,
                'fixed_charge'      => getAmount($data->fixed_charge,get_wallet_precision()),
                'percent_charge'    => getAmount($data->percent_charge,get_wallet_precision()),
                'min_limit'         => getAmount($data->min_limit,get_wallet_precision()),
                'max_limit'         => getAmount($data->max_limit,get_wallet_precision()),
                'daily_limit'       => getAmount($data->daily_limit,get_wallet_precision()),
                'monthly_limit'     => getAmount($data->monthly_limit,get_wallet_precision()),
            ];
        })->first();
         $cardReloadCharge = TransactionSetting::where('slug', 'reload_card')->where('status', 1)->get()->map(function ($data) {
            return [
                'id'                => $data->id,
                'slug'              => $data->slug,
                'title'             => $data->title,
                'fixed_charge'      => getAmount($data->fixed_charge, get_wallet_precision()),
                'percent_charge'    => getAmount($data->percent_charge, get_wallet_precision()),
                'min_limit'         => getAmount($data->min_limit, get_wallet_precision()),
                'max_limit'         => getAmount($data->max_limit, get_wallet_precision()),
                'daily_limit'       => getAmount($data->daily_limit, get_wallet_precision()),
                'monthly_limit'     => getAmount($data->monthly_limit, get_wallet_precision()),
            ];
        })->first();
        $cardWithdrawCharge = TransactionSetting::where('slug', 'withdraw_card')->where('status', 1)->get()->map(function ($data) {
            return [
                'id'                => $data->id,
                'slug'              => $data->slug,
                'title'             => $data->title,
                'fixed_charge'      => getAmount($data->fixed_charge, get_wallet_precision()),
                'percent_charge'    => getAmount($data->percent_charge, get_wallet_precision()),
                'min_limit'         => getAmount($data->min_limit, get_wallet_precision()),
                'max_limit'         => getAmount($data->max_limit, get_wallet_precision()),
                'daily_limit'       => getAmount($data->daily_limit, get_wallet_precision()),
                'monthly_limit'     => getAmount($data->monthly_limit, get_wallet_precision()),
            ];
        })->first();

        $data =[
            'base_curr'            => get_default_currency_code(),
            'cardCharge'           => (object)$cardCharge,
            'card_reload_charge'   => $cardReloadCharge,
            'card_withdraw_charge' => $cardWithdrawCharge,
        ];
        $message =  ['success'=>[__('Fess & Charges')]];
        return Helpers::success($data,$message);

    }
    //card details
    public function cardDetails(){
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = request()->card_id;
        $user = auth()->user();
        $myCard = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$myCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }
        if($myCard->card_status == StrowalletVirtualCard::STRING_STATUS_PENDING || $myCard->card_status == StrowalletVirtualCard::STRING_STATUS_PROCESSING){
            $card_details   = card_details($card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

            if($card_details['status'] == false){
                $error = ['error'=>[__("Your Card Is Pending! Please Contact With Admin")]];
                return Helpers::error($error);
            }

            $myCard->user_id     = Auth::user()->id;
            $myCard->card_name = $card_details['data']['card_detail']['card_name'];
            $myCard->card_status = $card_details['data']['card_detail']['card_status'];
            $myCard->card_number = $card_details['data']['card_detail']['card_number'];
            $myCard->last4       = $card_details['data']['card_detail']['last4'];
            $myCard->cvv         = $card_details['data']['card_detail']['cvv'];
            $myCard->expiry      = $card_details['data']['card_detail']['expiry'];
            $myCard->balance     = $card_details['data']['card_detail']['balance'];
            $myCard->status      = $card_details['data']['card_detail']['card_status'] == StrowalletVirtualCard::STRING_STATUS_ACTIVE ? true :  false ;
            $myCard->is_active   = $card_details['data']['card_detail']['card_status'] == StrowalletVirtualCard::STRING_STATUS_ACTIVE ? true :  false ;
            $myCard->card_holder_name = $card_details['data']['card_detail']['card_holder_name'] ?? null;
            $myCard->address          = $card_details['data']['card_detail']['address'] ?? null;
            $myCard->city             = $card_details['data']['card_detail']['city'] ?? null;
            $myCard->state            = $card_details['data']['card_detail']['state'] ?? null;
            $myCard->postal           = $card_details['data']['card_detail']['postal'] ?? null;
            $myCard->country          = $card_details['data']['card_detail']['country'] ?? null;
            $myCard->card_number_url  = $card_details['data']['card_detail']['card_number_url'] ?? null;
            $myCard->cvv_url          = $card_details['data']['card_detail']['cvv_url'] ?? null;
            $myCard->save();
        }
        $myCards = StrowalletVirtualCard::where('card_id',$card_id)->where('user_id',$user->id)->get()->map(function($data){
            $basic_settings = BasicSettings::first();

            $authCode        = $data->authorization_code;
            $authCodeSentAt  = $data->auth_code_send_at ? \Carbon\Carbon::parse($data->auth_code_send_at) : null;
            $authCodeExpired = !$authCodeSentAt || $authCodeSentAt->diffInMinutes(now()) >= 5;

            if(is_null($authCode)){
                $authorization_code = __("No OTP");
            }elseif($authCodeExpired){
                $authorization_code = __("OTP Expired");
            }else{
                $authorization_code = $authCode;
            }

            return[
                'id'                 => $data->id,
                'name'               => $data->name_on_card,
                'card_id'            => $data->card_id,
                'card_status'        => $data->card_status,
                'card_number'        => $data->card_number ?? '---- ---- ---- ----',
                'card_brand'         => $data->card_brand,
                'card_user_id'       => $data->card_user_id,
                'expiry'             => $data->expiry,
                'cvv'                => $data->cvv ?? '***',
                'card_type'          => ucwords($data->card_type),
                'amount'             => getAmount($data->balance,2),
                'currency'           => $data->currency,
                'card_holder_name'   => $data->card_holder_name,
                'address'            => $data->address,
                'city'               => $data->city,
                'state'              => $data->state,
                'postal'             => $data->postal,
                'country'            => $data->country,
                'card_number_url'    => $data->card_number_url,
                'cvv_url'            => $data->cvv_url,
                'authorization_code' => $authorization_code,
                'auth_code_send_at'  => $data->auth_code_send_at ?? null,
                'card_back_details'  => @$this->api->card_details,
                'card_bg'            => get_image(@$this->api->image,'card-api'),
                'site_title'         => @$basic_settings->site_name,
                'site_logo'          => get_logo(@$basic_settings,'dark'),
                'status'             => $data->is_active,
                'is_default'         => $data->is_default,
            ];
        })->first();
        $data =[
            'base_curr'      => get_default_currency_code(),
            'base_curr_rate' => get_default_currency_rate(),
            'myCards'        => $myCards,
        ];
        $message =  ['success'=>[__('card Details')]];
        return Helpers::success($data,$message);
    }
    public function makeDefaultOrRemove(Request $request) {
        $validator = Validator::make($request->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validate();
        $user = auth()->user();
        $targetCard =  StrowalletVirtualCard::where('card_id',$validated['card_id'])->where('user_id',$user->id)->first();
        if(!$targetCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        };
        $withOutTargetCards =  StrowalletVirtualCard::where('id','!=',$targetCard->id)->where('user_id',$user->id)->get();
        try{
            $targetCard->update([
                'is_default'         => $targetCard->is_default ? 0 : 1,
            ]);
            if(isset(  $withOutTargetCards)){
                foreach(  $withOutTargetCards as $card){
                    $card->is_default = false;
                    $card->save();
                }
            }
            $message =  ['success'=>[__('Status Updated Successfully')]];
            return Helpers::onlysuccess($message);

        }catch(Exception $e) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
    }
    // card transactions
    public function cardTransaction() {
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = request()->card_id;
        $user    = auth()->user();
        $card    = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$card){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $public_key = $this->api->config->strowallet_public_key;
        $base_url   = $this->api->config->strowallet_url;
        $mode       = $this->api->config->strowallet_mode ?? GlobalConst::SANDBOX;

        $params = [
            'public_key' => $public_key,
            'card_id'    => $card->card_id,
        ];
        if ($mode === GlobalConst::SANDBOX) $params['mode'] = 'sandbox';
        try{
            $client   = new \GuzzleHttp\Client();
            $response = $client->request('GET', $base_url . "nfc-card-transactions/", [
                'query'       => $params,
                'headers'     => [
                    'accept' => 'application/json',
                ],
                'timeout'     => 30,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
        }catch(Exception $e){
            $result = null;
        }

        if(isset($result['success']) && $result['success'] === true && isset($result['response'])){
            $data = $result['response'];
        }else{
            $data = [
                'card_transactions' => [] // Empty array for card_transactions
            ];

        }

        $message = ['success' => [__('Virtual Card Transaction')]];
        return Helpers::success($data,$message);
    }
    //card block
    public function cardBlock(Request $request){

        $validator = Validator::make($request->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = $request->card_id;
        $user    = auth()->user();

        $card    = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$card){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }
        if($card->is_active == false){
            $error = ['error'=>[__('Sorry,This Card Is Already Freeze')]];
            return Helpers::error($error);
        }

        $data = change_strowallet_card_status($card->card_id,'frozen',$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

        if($data['status'] == true) {
            $card->is_active = 0;
            $card->save();
            $message =  ['success'=>[__('Card Freeze successfully')]];
            return Helpers::onlysuccess($message);
        }else{
            $error = ['error'=>[$data['message'] ?: __("Something is wrong in your card")]];
            return Helpers::error($error);
        }

    }
    //unblock card
    public function cardUnBlock(Request $request){
        $validator  = Validator::make($request->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id    = $request->card_id;
        $user       = auth()->user();

        $card       = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$card){
            $error  = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }
        if($card->is_active == true){
            $error = ['error'=>[__('Sorry,This Card Is Already Unfreeze')]];
            return Helpers::error($error);
        }
        $data = change_strowallet_card_status($card->card_id,'active',$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

        if($data['status'] == true){
            $card->is_active = 1;
            $card->save();
            $message =  ['success'=>[__('Card UnFreeze successfully')]];
            return Helpers::onlysuccess($message);
        }else{
            $error = ['error'=>[$data['message'] ?: __("Something is wrong in your card")]];
            return Helpers::error($error);
        }

    }

    //card buy
    public function cardBuy(Request $request){

        $validator = Validator::make($request->all(), [
            'card_amount'   => 'required|numeric|gt:0',
            'currency'      => "required|string|exists:currencies,code",
            'from_currency' => "required|string|exists:currencies,code",
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $user = auth()->user();

        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        if(!$customer_kyc){
            $error = ['error'=>[__('A cardholder is required before creating a card. Please create a cardholder first.')]];
            return Helpers::error($error);
        }
        if(empty($customer_kyc->customer_id)){
            $error = ['error'=>[__('Your cardholder is not verified yet. Please update the cardholder information and try again.')]];
            return Helpers::error($error);
        }


        $formData      = $request->all();
        $kycData       = Arr::except($customer_kyc->toArray(), ['id', 'user_id', 'id_image', 'face_image', 'created_at', 'updated_at','faceImageData']);
        $formData      = array_merge($formData, $kycData);
        $amount        = $request->card_amount;
        $currency      = $formData['currency'];
        $basic_setting = BasicSettings::first();

        $supported_currency = ['USD'];
        if( !in_array($currency,$supported_currency??[])){
            $error = ['error'=>[$currency." ". __("Currency isn't supported for creating virtual card, Please contact")]];
            return Helpers::error($error);
        }

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($formData) {
            $q->where("code",$formData['from_currency'])->active();
        })->active()->first();
        if(!$wallet){
            $error = ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }

        $card_currency = Currency::active()->where('code',$formData['currency'])->first();
        if(!$card_currency){
            $error = ['error'=>[__('Card Currency Not Found')]];
            return Helpers::error($error);
        }

        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();
        $charges = $this->cardCharges($formData['card_amount'],$cardCharge,$wallet,$card_currency);

        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];

        if($amount < $minLimit || $amount > $maxLimit){
            $error = ['error'=>[__('Please follow the transaction limit')]];
            return Helpers::error($error);
        }
        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
           $error = ['error'=>[__($errorData['message']??"")]];
            return Helpers::error($error);
        }
        if($charges['payable'] > $wallet->balance){
            $error = ['error'=>[__('Sorry, insufficient balance')]];
            return Helpers::error($error);
        }

        $customer_card  = StrowalletVirtualCard::where('user_id',$user->id)->count();

        if($customer_card >= $this->card_limit){
            $error = ['error'=>[__("Sorry! You can not create more than")." ".$this->card_limit ." ".__("card using the same email address.")]];
            return Helpers::error($error);
        }

        // for live code
        $created_card = create_strowallet_virtual_card($this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$formData);

        if($created_card['status'] == false){
            $error = ['error'=>(array) parse_error($created_card, __("Card creation failed. Please try again."))];
            return Helpers::error($error);
        }

        $strowallet_card                    = new StrowalletVirtualCard();
        $strowallet_card->user_id           = $user->id;
        $strowallet_card->name_on_card      = $created_card['data']['name_on_card'];
        $strowallet_card->card_id           = $created_card['data']['card_id'];
        $strowallet_card->card_created_date = $created_card['data']['card_created_date'];
        $strowallet_card->card_type         = $created_card['data']['card_type'];
        $strowallet_card->card_brand        = $created_card['data']['card_brand'];
        $strowallet_card->card_user_id      = $created_card['data']['card_user_id'];
        $strowallet_card->reference         = $created_card['data']['reference'];
        $strowallet_card->card_status       = $created_card['data']['card_status'];
        $strowallet_card->customer_id       = $created_card['data']['customer_id'];
        $strowallet_card->customer_email    = $request->email;
        $strowallet_card->balance           = 0;
        $strowallet_card->currency          = $currency;
        $strowallet_card->mode              = $this->api_mode;
        $strowallet_card->status            = $created_card['data']['card_status'] == StrowalletVirtualCard::STRING_STATUS_COMPLETED ? true : false;
        $strowallet_card->is_active         = $created_card['data']['card_status'] == StrowalletVirtualCard::STRING_STATUS_COMPLETED ? true : false;
        $strowallet_card->save();

        $trx_id =  'CB'.getTrxNum();
        try{
            $sender = $this->insertCardBuy($trx_id,$user,$wallet,$amount,$strowallet_card,$charges,$created_card['data']);
            $this->insertBuyCardCharge($charges,$user,$sender,$strowallet_card->card_number??"---- ----- ---- ----");

            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'            => $trx_id,
                    'title'             => "Virtual Card (Buy Card)",
                    'request_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'           =>  get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                    'charges'           => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                    'card_amount'       => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'card_pan'          =>  "---- ----- ---- ----",
                    'status'            =>  $strowallet_card->card_status??"",
                ];
                try{
                    $user->notify(new CreateMail($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }
            if( $basic_setting->sms_notification == true){
                try{
                    sendSms($user,'VIRTUAL_CARD_BUY',[
                        'request_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'       => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'          => "---- ----- ---- ----",
                        'trx'               => $trx_id,
                        'time'              =>  now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }

            //admin notification
            $this->adminNotification($trx_id,$charges,$amount,$user,$strowallet_card);
            $message =  ['success'=>[__('Virtual Card Buy Successfully')]];
            return Helpers::onlysuccess($message);

        }catch(Exception $e){

            $error =  ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }
    public function insertCardBuy($trx_id,$user,$wallet,$amount,$strowallet_card,$charges,$api_response) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details =[
            'card_info'    => $strowallet_card??'',
            'charges'      => $charges,
            'api_response' => $api_response ?? [],
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => $user->id,
                'user_wallet_id'              => $authWallet->id,
                'payment_gateway_currency_id' => null,
                'type'                        => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                      => $trx_id,
                'request_amount'              => $amount,
                'payable'                     => $charges['payable'],
                'available_balance'           => $afterCharge,
                'remark'                      => PaymentGatewayConst::CARDBUY,
                'details'                     => json_encode($details),
                'attribute'                   => PaymentGatewayConst::RECEIVED,
                'status'                      => true,
                'callback_ref'                => $api_response['card_id'] ?? null,
                'created_at'                  => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function insertBuyCardCharge($charges,$user,$id,$card_number) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
               'transaction_id'     => $id,
                'percent_charge'    => $charges['percent_charge'],
                'fixed_charge'      => $charges['fixed_charge'],
                'total_charge'      => $charges['total_charge'],
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>__('buy Card'),
                'message'       => __('Buy card successful')." ".$card_number??"---- ---- ---- ----",
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_BUY,
                'user_id'   => $user->id,
                'message'   => $notification_content,
            ]);

            //Push Notifications
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepareApi([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    //update user balance
    public function updateSenderWalletBalance($authWallet,$afterCharge) {
        $authWallet->update([
            'balance'   => $afterCharge,
        ]);
    }
    /**
     * Card Fund
     */
    public function cardFundConfirm(Request $request){
        $validator = Validator::make($request->all(), [
            'card_id'       => 'required',
            'fund_amount'   => 'required|numeric|gt:0',
            'currency'      => "required|string|exists:currencies,code",
            'from_currency' => "required|string|exists:currencies,code",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validate();
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $amount = $request->fund_amount;

        $myCard =  StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$request->card_id)->first();
        if(!$myCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet){
            $error = ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }
        $cardCharge = TransactionSetting::where('slug','reload_card')->where('status',1)->first();
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency){
            $error = ['error'=>[__('Card Currency Not Found')]];
            return Helpers::error($error);
        }
        $charges = $this->cardCharges($validated['fund_amount'],$cardCharge,$wallet,$card_currency);
        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];

        if($amount < $minLimit || $amount > $maxLimit){
            $error = ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);
        }
        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            $error = ['error'=>[__($errorData['message'] ?? __("Something went wrong! Please try again."))]];
            return Helpers::error($error);
        }

        if($charges['payable'] > $wallet->balance){
            $error = ['error'=>[__("Your Wallet Balance Is Insufficient")]];
            return Helpers::error($error);
        }

        $public_key     = $this->api->config->strowallet_public_key;
        $base_url       = $this->api->config->strowallet_url;
        $mode           = $this->api->config->strowallet_mode??GlobalConst::SANDBOX;
        $form_params    = [
           'public_key' => $public_key,
           'card_id'    => $myCard->card_id,
           'amount'     => $amount,
           'type'       => 'fund',
        ];
        if ($mode === GlobalConst::SANDBOX) {
            $form_params['mode'] = "sandbox";
        }


        $client = new \GuzzleHttp\Client();

        try{
            $response               = $client->request('POST', $base_url.'fund-withdraw-nfccard/', [
                'headers'           => [
                    'accept'        => 'application/json',
                ],
                'query'             => $form_params,
                'timeout'           => 30,
                'http_errors'       => false,
                'allow_redirects'   => ['strict' => true],
            ]);

            $decodedResult  = json_decode($response->getBody()->getContents(), true);
        }catch(Exception $e){
            $decodedResult  = null;
        }

         if(!empty($decodedResult['success'])  && $decodedResult['success'] == true){

            //added fund amount to card
            $successStatuses = [
                StrowalletVirtualCard::STRING_STATUS_SUCCESS,
                StrowalletVirtualCard::STRING_STATUS_ACTIVE,
                StrowalletVirtualCard::STRING_STATUS_COMPLETED,
            ];

            if (in_array($decodedResult['response']['status'], $successStatuses)) {
                $myCard->increment('balance', $amount);
            }

            $trx_id = 'CF'.getTrxNum();
            $sender = $this->insertCardFund($trx_id,$user,$wallet,$amount,$myCard,$charges,$decodedResult['response']);
            $this->insertFundCardCharge($charges,$user,$sender,$myCard->card_number,$amount);
            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'        => $trx_id,
                    'request_amount'=> get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'       => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                    'charges'       => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                    'card_amount'   => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'card_pan'      => $myCard->card_number??"---- ----- ---- ----",
                    'status'        => "Success",
                ];
                try{
                    $user->notify(new Fund($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }
            if( $basic_setting->sms_notification == true){
                try{
                    sendSms($user,'VIRTUAL_CARD_FUND',[
                        'request_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'       => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'          =>  $myCard->card_number??"---- ----- ---- ----",
                        'trx'               => $trx_id,
                        'time'              =>  now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }

            //admin notification
            $this->adminNotificationFund($trx_id,$charges,$amount,$user,$myCard);
            $message =  ['success'=>[__('Card Funded Successfully')]];
            return Helpers::onlysuccess($message);

        }else{
            $error = ['error'=>[@$decodedResult['message'].' ,'.__('Please Contact With Administration.')]];
            return Helpers::error($error);
        }

    }
    //card fund helper
    public function insertCardFund($trx_id,$user,$wallet,$amount,$myCard,$charges,$card_response) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details =[
            'card_info' =>   $myCard??'',
            'charges'   =>   $charges,
        ];
        $status = $card_response['status'] == StrowalletVirtualCard::STRING_STATUS_PENDING ? PaymentGatewayConst::STATUSPENDING :  PaymentGatewayConst::STATUSSUCCESS;
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $charges['payable'],
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(PaymentGatewayConst::CARDFUND),
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                      => $status,
                'callback_ref'                => $card_response['reference'] ?? "",
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function insertFundCardCharge($charges,$user,$id,$card_number,$amount) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
               'transaction_id'    => $id,
                'percent_charge'    => $charges['percent_charge'],
                'fixed_charge'      => $charges['fixed_charge'],
                'total_charge'      => $charges['total_charge'],
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>__("Card Fund"),
                'message'       => __("Card fund successful card")." : ".$card_number.' '.get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_FUND,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);

            //Push Notifications
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepareApi([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    public function cardWithdrawConfirm(Request $request){

        $validator = Validator::make($request->all(), [
            'card_id'         => 'required|string',
            'withdraw_amount' => 'required|numeric|gt:0',
            'currency'        => "required|string",
            'from_currency'   => "required|string|exists:currencies,code",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated     = $validator->validate();
        $basic_setting = BasicSettings::first();
        $mode          = $this->api->config->strowallet_mode ?? GlobalConst::SANDBOX;
        $user          = userGuard()['user'];
        $amount        = $validated['withdraw_amount'];

        $myCard =  StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$request->card_id)->first();
        if(!$myCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $get_card  =  card_details($myCard->card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);
        if($get_card['status'] == false){
            $error = ['error'=>[parse_error($get_card, __("Something is wrong in your card"))]];
            return Helpers::error($error);
        }

        // check real card balance
        if($mode != GlobalConst::SANDBOX){
            if($amount > $get_card['data']['card_detail']['balance']){
                $error = ['error'=>[__("Your card doesn't have enough funds to complete this transaction")]];
                return Helpers::error($error);
            }
        }

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet){
            $error = ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }

        $cardCharge = TransactionSetting::where('slug','withdraw_card')->where('status',1)->first();
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency){
            $error = ['error'=>[__('Card Currency Not Found')]];
            return Helpers::error($error);
        }

        $charges = $this->cardWithdrawCharges($amount,$cardCharge,$wallet,$card_currency);

        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit){
            $error = ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);

        }
        //daily and monthly

        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            $error = ['error'=>[__($errorData['message'] ?? __("Something went wrong! Please try again."))]];
            return Helpers::error($error);
        }

        $withdraw_request = withdraw_virtual_card($this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$myCard->card_id, $amount);
        if($withdraw_request['status'] == false){
            $error = ['error'=>(array) parse_error($withdraw_request, __("Something is wrong in your card"))];
            return Helpers::error($error);
        }

        try{
            //after success
            $successStatuses = [
                StrowalletVirtualCard::STRING_STATUS_SUCCESS,
                StrowalletVirtualCard::STRING_STATUS_ACTIVE,
                StrowalletVirtualCard::STRING_STATUS_COMPLETED,
            ];

            if (in_array($withdraw_request['data']['status'], $successStatuses)) {
                $myCard->decrement('balance', $amount);
            }

            //create local transaction
            $trx_id = 'CW-'.getTrxNum();
            $sender = $this->insertCardWithdrawal($trx_id,$user,$wallet,$amount,$myCard,$charges,$withdraw_request['data']);

            $this->insertWithdrawalCardCharge($charges,$user,$sender,$myCard->card_number,$amount);
            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'         => $trx_id,
                    'title'          => "Virtual Card (Withdraw Amount)",
                    'request_amount' => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'        => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                    'charges'        => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                    'card_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'card_pan'       => $myCard->card_number ?? "---- ----- ---- ----",
                    'status'         => "Success",
                ];

                try{
                    $user->notify(new Withdraw($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }

            if( $basic_setting->sms_notification == true){
                try{
                    sendSms($user,'VIRTUAL_CARD_WITHDRAW',[
                        'request_amount' => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'       => $myCard->card_number??"---- ----- ---- ----",
                        'trx'            => $trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }

            //admin notification
            $this->adminNotificationWithdraw($trx_id,$charges,$amount,$user,$myCard);
            $message =  ['success'=>[__('Card Withdrawal Successful')]];
            return Helpers::onlysuccess($message);
        }catch(Exception $e){
            $error = ['error'=>[___("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }
     //for withdraw helpers
    public function insertCardWithdrawal($trx_id,$user,$wallet,$amount,$myCard,$charges,$withdraw_request) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance + $charges['payable']);
        $details =[
            'card_info' =>   $myCard ?? '',
            'charges'   =>   $charges,
        ];

        $status = $withdraw_request['status'] == StrowalletVirtualCard::STRING_STATUS_PENDING ? PaymentGatewayConst::STATUSPENDING :  PaymentGatewayConst::STATUSSUCCESS;

        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => $user->id,
                'user_wallet_id'              => $authWallet->id,
                'payment_gateway_currency_id' => null,
                'type'                        => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                      => $trx_id,
                'request_amount'              => $amount,
                'payable'                     => $charges['payable'],
                'available_balance'           => $afterCharge,
                'remark'                      => ucwords(PaymentGatewayConst::CARDWITHDRAW),
                'details'                     => json_encode($details),
                'attribute'                   => PaymentGatewayConst::RECEIVED,
                'status'                      => $status,
                'callback_ref'                => $withdraw_request['reference'],
                'created_at'                  => now(),
            ]);
            // $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function insertWithdrawalCardCharge($charges,$user,$id,$card_number,$amount) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $charges['percent_charge'],
                'fixed_charge'      => $charges['fixed_charge'],
                'total_charge'      => $charges['total_charge'],
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>__("Withdraw Amount"),
                'message'       => __("Card Withdrawal Successful Card")." : ".$card_number.' '.get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'    => NotificationConst::CARD_WITHDRAW,
                'user_id' => $user->id,
                'message' => $notification_content,
            ]);

          //Push Notifications
          if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }
           DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    //admin notification
    public function adminNotification($trx_id,$charges,$amount,$user,$v_card){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Buy Card)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("card Masked")." : ".$v_card->card_number??"---- ----- ---- ----"."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Virtual Card (Buy Card)")." (".userGuard()['type'].")",
            'push_content' => __('web_trx_id')." : ".$trx_id." ".__("request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])." ".__("card Masked")." : ".$v_card->card_number??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::CARD_BUY,
            'admin_db_title' => "Virtual Card Buy"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit']).","."Card Masked"." : ".$v_card->card_number??"---- ----- ---- ----"." (".$user->email.")",
        ];

        try{
            //notification
            (new NotificationHelper())->admin(['admin.virtual.card.logs','admin.virtual.card.export.data'])
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
    public function adminNotificationFund($trx_id,$charges,$amount,$user,$myCard){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Fund Amount)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("card Masked")." : ".$myCard->masked_card??"---- ----- ---- ----"."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Virtual Card (Fund Amount)")." (".userGuard()['type'].")",
            'push_content' => __('web_trx_id')." : ".$trx_id." ".__("request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])." ".__("card Masked")." : ".$myCard->masked_card??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::CARD_FUND,
            'admin_db_title' => "Virtual Card Funded"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit']).","."Card Masked"." : ".$myCard->card_number??"---- ----- ---- ----"." (".$user->email.")",
        ];

        try{
            //notification
            (new NotificationHelper())->admin(['admin.virtual.card.logs','admin.virtual.card.export.data'])
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
    public function adminNotificationWithdraw($trx_id,$charges,$amount,$user,$myCard){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Withdraw Amount)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Will Receive")." : ".get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Card Masked")." : ".$myCard->card_number??"---- ----- ---- ----"."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Virtual Card (Withdraw Amount)")." (".userGuard()['type'].")",
            'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])." ".__("Card Masked")." : ".$myCard->card_number??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::WITHDRAWMONEY,
            'admin_db_title' => "Virtual Card Withdrawal"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit']).","."Card Masked"." : ".$myCard->card_number ?? "---- ----- ---- ----"." (".$user->email.")",
        ];

        try{
            //notification
            (new NotificationHelper())->admin(['admin.virtual.card.logs'])
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
    //card buy charges function
    public function cardCharges($amount,$charges,$wallet,$card_currency){
        $sPrecision = get_wallet_precision($wallet->currency);
        $exchange_rate = $wallet->currency->rate/$card_currency->rate;

        $data['exchange_rate']         = $exchange_rate;
        $data['card_amount']           = $amount;
        $data['card_currency']         = $card_currency->code;
        $data['card_currency_rate']    = $card_currency->rate;

        $data['from_amount']           = $amount * $exchange_rate;
        $data['from_currency']         = $wallet->currency->code;
        $data['from_currency_rate']    = $wallet->currency->rate;

        $data['percent_charge']        = ($amount / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge']          = $wallet->currency->rate * $charges->fixed_charge ?? 0;
        $data['total_charge']          = $data['percent_charge'] + $data['fixed_charge'];
        $data['from_wallet_balance']   = $wallet->balance;
        $data['payable']               = $data['from_amount'] + $data['total_charge'];
        $data['card_platform']         = "StroWallet";
        $data['precision_digit']       = $sPrecision;


        return $data;

    }
    public function cardWithdrawCharges($amount,$charges,$wallet,$card_currency){
        $sPrecision = get_wallet_precision($wallet->currency);
        $exchange_rate = $wallet->currency->rate/$card_currency->rate;

        $data['exchange_rate']         = $exchange_rate;
        $data['card_amount']           = $amount;
        $data['card_currency']         = $card_currency->currency_code;
        $data['card_currency_rate']    = $card_currency->rate;

        $data['from_amount']           = $amount * $exchange_rate;
        $data['from_currency']         = $wallet->currency->code;
        $data['from_currency_rate']    = $wallet->currency->rate;

        $data['percent_charge']        = (($data['card_amount'] *  $data['exchange_rate']) / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge']          = ($charges->fixed_charge * $exchange_rate);
        $data['total_charge']          = $data['percent_charge'] + $data['fixed_charge'];
        $data['from_wallet_balance']   = $wallet->balance;
        $data['payable']               = $data['from_amount'] - $data['total_charge'];
        $data['card_platform']         = "Strowallet";
        $data['precision_digit']       = $sPrecision;

        return $data;

    }
    public function apiErrorHandle($apiErrors){
        $error = ['error' => []];
        if (isset($apiErrors)) {
            if (is_array($apiErrors)) {
                foreach ($apiErrors as $field => $messages) {
                    if (is_array($messages)) {
                        foreach ($messages as $message) {
                            $error['error'][] = $message;
                        }
                    } else {
                        $error['error'][] = $messages;
                    }
                }
            } else {
                $error['error'][] = $apiErrors;
            }
        }

        $errorMessages = array_map(function($message) {
            return rtrim($message, '.');
        }, $error['error']);

        $errorString = implode(', ', $errorMessages);
        $errorString .= '.';

        $error = ['error' => [$errorString ?? __("Something went wrong! Please try again.")]];
        return Helpers::error($error);

    }
    public function webhookTransaction(){
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $card_id = request()->card_id;
        $user = authGuardApi()['user'];
        $myCard = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
        if(!$myCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }
        $logs = StrowalletWebhookData::where('user_id',$user->id)->where('cardId',$card_id)->latest()->get()->map(function($item){
           if($item->event === global_const()::TERMINATED){
                return[
                    'event_type'                => $item->event,
                    'event'                     => str_replace(' ', ' ', ucwords(str_replace('.', ' ', $item->event))),
                    'amount'                    => get_amount($item->data->amount ?? 0,$item->card_currency,get_wallet_precision()),
                    'transition_id'             => $item->transaction_id ?? "",
                    'card_id'                   => $item->cardId ?? "",
                    'reference'                 => $item->data->reference ?? "",
                    'status'                    => $item->data->status ?? "",
                    'balance_before_termination'  => get_amount($item->data->balanceBeforeTermination ?? 0,$item->card_currency,get_wallet_precision())
                ];
           }elseif($item->event === global_const()::CROSSBORDER){
                return[
                    'event_type'                => $item->event,
                    'event'                     => str_replace(' ', ' ', ucwords(str_replace('.', ' ', $item->event))),
                    'amount'                    => get_amount($item->data->amount ?? 0,$item->card_currency,get_wallet_precision()),
                    'transition_id'             => $item->transaction_id ?? "",
                    'card_id'                   => $item->cardId ?? "",
                    'reference'                 => $item->data->reference ?? "",
                    'charge_amount'             => get_amount($item->data->chargedAmount ?? 0,$item->card_currency,get_wallet_precision()),
                    'narrative'                 => $item->data->narrative ?? "",
                    'status'                    => $item->data->status ?? "",
                    'created_at'                => $item->data->createdAt ?? "",
                ];
           }elseif($item->event === global_const()::DECLINED){
                return[
                    'event_type'                => $item->event,
                    'event'                     => str_replace(' ', ' ', ucwords(str_replace('.', ' ', $item->event))),
                    'amount'                    => get_amount($item->data->amount ?? 0,$item->card_currency,get_wallet_precision()),
                    'transition_id'             => $item->transaction_id ?? "",
                    'card_id'                   => $item->cardId ?? "",
                    'reference'                 => $item->data->reference ?? "",
                    'narrative'                 => $item->data->narrative ?? "",
                    'reason'                    => $item->data->reason ?? "",
                    'status'                    => $item->data->status ?? "",
                    'created_at'                => $item->data->createdAt ?? "",
                ];
           }

        });
        $data =[
            'transactions'=> $logs,
        ];
        $message =  ['success'=>[__('Webhook Logs')]];
        return Helpers::success($data,$message);

    }
}
