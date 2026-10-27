<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\Admin\Currency;
use App\Models\VirtualCardApi;
use App\Models\UserNotification;
use App\Http\Helpers\Api\Helpers;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Models\CardyfieVirtualCard;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\CardyFieHelper;
use App\Models\CardyfieCardCustomer;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\TransactionLimit;
use App\Http\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Notifications\User\VirtualCard\Fund;
use App\Providers\Admin\BasicSettingsProvider;
use App\Notifications\User\VirtualCard\Withdraw;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\VirtualCard\CreateMail;

class CardyFieVirtualCardController extends Controller
{

    protected $api;
    protected $card_limit;
    protected $api_mode;
    protected $basic_settings;
    protected $card_supported_currencies;
    protected $cardCharges;


    public function __construct(){
        $cardApi              = VirtualCardApi::first();
        $this->api            = $cardApi;
        $this->card_limit     = $cardApi->card_limit;
        $this->api_mode       = $cardApi->config->cardyfie_mode;
        $this->basic_settings = BasicSettingsProvider::get();

         // Fetch currencies dynamically
        $cardCurrencies = (new CardyFieHelper())->cardCurrencies();
        $currencies = [];
        $countries  = [];

        if (!empty($cardCurrencies['data']['currencies'])) {
            foreach ($cardCurrencies['data']['currencies'] as $currencyItem) {
                $currencies[] = $currencyItem['currency']['code'] ?? 'USD';
                $countries[]  = $currencyItem['currency']['country_name'] ?? 'United States';
            }
        }

        // fallback defaults if API gives nothing
        if (empty($currencies)) {
            $currencies = ["USD"];
        }
        if (empty($countries)) {
            $countries = ["United States"];
        }

        $this->card_supported_currencies = [
            "currencies" => $currencies,
            "countries"  => $countries,
        ];

        $this->cardCharges     = (object)[
            'universal_card_issues_fee'  => floatval($this->api->config->cardyfie_universal_card_issues_fee ?? 0),
            'platinum_card_issues_fee'   => floatval($this->api->config->cardyfie_platinum_card_issues_fee ?? 0),
            'card_deposit_fixed_fee'     => floatval($this->api->config->cardyfie_card_deposit_fixed_fee ?? 0),
            'card_deposit_percent_fee'   => floatval($this->api->config->cardyfie_card_deposit_percent_fee ?? 0),
            'card_withdraw_fixed_fee'    => floatval($this->api->config->cardyfie_card_withdraw_fixed_fee ?? 0),
            'card_withdraw_percent_fee'  => floatval($this->api->config->cardyfie_card_withdraw_percent_fee ?? 0),
            'card_maintenance_fixed_fee' => floatval($this->api->config->cardyfie_card_maintenance_fixed_fee ?? 0),
            'min_limit'                  => floatval($this->api->config->cardyfie_min_limit ?? 0),
            'max_limit'                  => floatval($this->api->config->cardyfie_max_limit ?? 0),
            'daily_limit'                => floatval($this->api->config->cardyfie_daily_limit ?? 0),
            'monthly_limit'              => floatval($this->api->config->cardyfie_monthly_limit ?? 0),
        ];
    }

    public function index(){
        $user = auth()->user();
        $card_basic_info = [
            'card_create_limit' => $this->api->card_limit,
            'card_back_details' => $this->api->card_details,
            'card_bg'           => get_image(@$this->api->image,'card-api'),
            'site_title'        => $this->basic_settings->site_name,
            'site_logo'         => get_logo(@$this->basic_settings,'dark'),
            'site_fav'          => get_fav($this->basic_settings,'dark'),
        ];


        $card_mode = strtoupper(cardyFieCardMode($this->api_mode));
        $query = CardyfieVirtualCard::where('user_id', $user->id)
            ->where('env', $card_mode)
            ->latest();

        if ($this->card_limit > 0) {
            $query->limit($this->card_limit);
        }

        $myCards = $query->get()->map(function($data) {
                $statusInfo = [
                    "PROCESSING" => "PROCESSING",
                    "ENABLED"    => "ENABLED",
                    "FREEZE"     => "FREEZE",
                    "CLOSED"     => "CLOSED",
                ];
                return[
                    'id'            => $data->id,
                    'reference_id'  => $data->reference_id,
                    'ulid'          => $data->ulid,
                    'customer_ulid' => $data->customer_ulid,
                    'card_name'     => $data->card_name,
                    'amount'        => floatval($data->amount ?? 0),
                    'currency'      => $data->currency,
                    'card_tier'     => $data->card_tier ?? '',
                    'card_type'     => $data->card_type ?? '',
                    'card_exp_time' => $data->card_exp_time,
                    'masked_pan'    => $data->masked_pan,
                    'address'       => $data->address,
                    'status'        => $data->status,
                    'env'           => $data->env,
                    'is_default'    => $data->is_default,
                    'status_info'   => (object)$statusInfo,
                ];
            });

            $cardyfie_card_charge = [
                'universal_card_issues_fee' => floatval($this->api->config->cardyfie_universal_card_issues_fee ?? 0),
                'platinum_card_issues_fee'  => floatval($this->api->config->cardyfie_platinum_card_issues_fee ?? 0),
                'card_deposit_fixed_fee'    => floatval($this->api->config->cardyfie_card_deposit_fixed_fee ?? 0),
                'card_deposit_percent_fee'  => floatval($this->api->config->cardyfie_card_deposit_percent_fee ?? 0),
                'card_withdraw_fixed_fee'   => floatval($this->api->config->cardyfie_card_withdraw_fixed_fee ?? 0),
                'card_withdraw_percent_fee' => floatval($this->api->config->cardyfie_card_withdraw_percent_fee ?? 0),
                'min_limit'                 => floatval($this->api->config->cardyfie_min_limit ?? 0),
                'max_limit'                 => floatval($this->api->config->cardyfie_max_limit ?? 0),
                'daily_limit'               => floatval($this->api->config->cardyfie_daily_limit ?? 0),
                'monthly_limit'             => floatval($this->api->config->cardyfie_monthly_limit ?? 0),
            ];

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
                'card_number'      => $item->details->card_info->card_pan??$item->details->card_info->maskedPan??$item->details->card_info->card_number ?? $item->details->card_info->masked_pan ??"---- ---- ---- ----",
                'current_balance'  => get_amount($item->available_balance,$item->details->charges->from_currency??get_default_currency_code(),get_wallet_precision($item->creator_wallet->currency)),
                'status'           => $item->stringStatus->value,
                'status_value'     => $item->status,
                'date_time'        => $item->created_at,
                'status_info'      => (object)$statusInfo,

                ];
            });
            $userWallet = user_wallets(authGuardApi(),'user_id');


            $card_customer  = $user->cardyfieCardCustomer->where('env', strtoupper($card_mode))->first();
            $totalCards = CardyfieVirtualCard::where('user_id',$user->id)->where('env',strtoupper($card_mode))->count() ?? 0;

            $supported_currency = support_currencies($this->card_supported_currencies['currencies']);
            $get_remaining_fields = [
                'transaction_type'  =>  PaymentGatewayConst::VIRTUALCARD,
                'attribute'         =>  PaymentGatewayConst::RECEIVED,
            ];
            $data =[
                'base_curr'                 => get_default_currency_code(),
                'base_curr_rate'            => getAmount(get_default_currency_rate(),get_wallet_precision()),
                'get_remaining_fields'      => (object) $get_remaining_fields,
                'supported_currency'        => $supported_currency,
                'card_create_action'        => ($this->card_limit > 0 && $totalCards >=  $this->card_limit) ? false : true,
                'customer_create_status'    => $card_customer == null ? true : false,
                'card_basic_info'           => (object) $card_basic_info,
                'myCards'                   => $myCards,
                'user'                      => $user,
                'userWallet'                => (object)$userWallet,
                'cardCharge'                => (object)$cardyfie_card_charge,
                'transactions'              => $transactions,
            ];
            $message =  ['success'=>[__('Virtual Card')]];
            return Helpers::success($data,$message);
    }

    public function createPage(){
        $user       = authGuardApi()['user'];
        $api_mode  = $this->api->config->cardyfie_mode ?? null;
        $card_mode = cardyFieCardMode($api_mode);
        $customer  = $user->cardyfieCardCustomer->where('env', strtoupper($card_mode))->first();

        $customer_exist_status  =  $customer != null ? true : false;
        $card_create_status     = ( $customer?->status == GlobalConst::CARD_CUSTOMER_APPROVED_STATUS)  ? true : false;


        if( $customer_exist_status){
            $customer_exist =   $customer;
        }else{
            $customer_exist =[
                "id"             => "",
                "user_type"      => "",
                "user_id"        => "",
                "ulid"           => "",
                "reference_id"   => "",
                "first_name"     => "",
                "last_name"      => "",
                "email"          => "",
                "date_of_birth"  => "",
                "id_number"      => "",
                "id_front_image" => "",
                "id_back_image"  => "",
                "user_image"     => "",
                "house_number"   => "",
                "address_line_1" => "",
                "city"           => "",
                "state"          => "",
                "zip_code"       => "",
                "country"        => "",
                "status"         => "",
                "meta"           => "",
                "status"         => "",
                "env"            => "",
                "created_at"     => "",
                "updated_at"     => "",
                "idFontImage"    => "",
                "idBackImage"    => "",
                "userImage"      => "",
            ];
            $customer_exist = (object) $customer_exist;
        }
       $customer_pending_text = __("Please wait until your customer status is APPROVED. Once it is APPROVED, you can continue with the card creation.");



        $data =[
            'user'                  => $user,
            'customer_exist_status' => $customer_exist_status,
            'customer_pending_text' => $customer_pending_text,
            'customer_exist'        => $customer_exist,
            'card_create_status'    => $card_create_status,
        ];
        $message =  ['success'=>[__('Data Fetch Successfully')]];
        return Helpers::success($data,$message);

    }

    public function createCustomer(Request $request){

        $validator = Validator::make($request->all(), [
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|max:150|unique:cardyfie_card_customers,email', // check unique in users table
            'date_of_birth'   => 'required|date',
            'identity_type'   => 'required|in:nid,passport,bvn',
            'identity_number' => 'required|string|max:50',
            'id_front_image'  => 'required|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'id_back_image'   => 'required|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'user_image'      => 'required|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'house_number'    => 'required|string|max:150',
            'country'         => 'required|string|max:200',
            'city'            => 'required|string|max:200',
            'state'           => 'required|string|max:200',
            'zip_code'        => 'required|string|max:20',
            'address'         => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated  = $validator->validate();
        $user       = userGuard()['user'];

        $api_mode  = $this->api->config->cardyfie_mode ?? null;
        $card_mode = cardyFieCardMode($api_mode);
        $card_customer  = $user->cardyfieCardCustomer->where('env', strtoupper($card_mode))->first();
        try{
            if( $card_customer == null){
                if ($request->hasFile("id_front_image")) {
                    $imageData = saveImageAndGetUrl($request->file("id_front_image"), 'card-kyc-images');
                    $validated['id_front_image'] = $imageData['url'];
                    $uploadedImages[] = $imageData['path'];
                }

                // id back image
                if ($request->hasFile("id_back_image")) {
                    $imageData = saveImageAndGetUrl($request->file("id_back_image"), 'card-kyc-images');
                    $validated['id_back_image'] = $imageData['url'];
                    $uploadedImages[] = $imageData['path'];
                }

                // user image
                if ($request->hasFile("user_image")) {
                    $imageData = saveImageAndGetUrl($request->file("user_image"), 'card-kyc-images');
                    $validated['user_image'] = $imageData['url'];
                    $uploadedImages[] = $imageData['path'];
                }
                $validated['reference_id'] = "ref-".getTrxNum();

                // Clean mapping for DB insert
                    $storeData =  CardyfieCardCustomer::create([
                        'user_type'      => "USER",
                        'user_id'        => $user->id,
                        'reference_id'   => $validated['reference_id'],
                        'first_name'     => $validated['first_name'],
                        'last_name'      => $validated['last_name'],
                        'email'          => $validated['email'],
                        'date_of_birth'  => $validated['date_of_birth'],
                        'id_type'        => $validated['identity_type'],
                        'id_number'      => $validated['identity_number'],
                        'id_front_image' => $validated['id_front_image'],
                        'id_back_image'  => $validated['id_back_image'],
                        'user_image'     => $validated['user_image'],
                        'house_number'   => $validated['house_number'],
                        'address_line_1' => $validated['address'],
                        'city'           => $validated['city'],
                        'state'          => $validated['state'],
                        'zip_code'       => $validated['zip_code'],
                        'country'        => $validated['country'],
                        'status'         =>"PENDING",
                        'env'            => strtoupper($card_mode),
                    ]);



                $createCustomer = (new CardyFieHelper())->createCustomer($validated);

                if( $createCustomer['status'] == false){
                    // delete all uploaded images if API failed
                    foreach ($uploadedImages as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    $storeData->delete();
                    return $this->apiErrorHandle($createCustomer["message"]);

                }

                // try{
                //     if($createCustomer['message'][0] == "Customer already exists with this email address!" || $createCustomer['message'][0] == "Customer already registered with this email."){
                //         foreach ($uploadedImages as $filePath) {
                //             if (file_exists($filePath)) {
                //                 unlink($filePath);
                //             }
                //         }
                //         $storeData->delete();

                //         $message =  ['error'=>[ $createCustomer['message'][0] ?? __("Something went wrong! Please try again.")]];
                //         return Helpers::error($message);

                //     }

                // }catch(Exception $e){
                //     $message =  ['error'=>[ __("Something went wrong! Please try again.")]];
                //     return Helpers::error($message);
                // }

                //update few fields after store
                $storeData->ulid   = $createCustomer['data']['customer']['ulid'];
                $storeData->meta   = $createCustomer['data']['customer']['meta'];
                $storeData->status = $createCustomer['data']['customer']['status'];
                $storeData->save();
            }else{
                $message =  ['error'=>[ __("Customer already exists")]];
                return Helpers::error($message);
            }

            $message =  ['success'=>[$createCustomer['message'][0] ?? __('Customer has been created successfully.')]];
            return Helpers::onlysuccess($message);

        }catch(Exception $e){
            $message =  ['error'=>[ __("Something went wrong! Please try again.")]];
            return Helpers::error($message);
        }

    }

    public function editCustomerPage(){
        $user          = userGuard()['user'];
        $api_mode      = $this->api->config->cardyfie_mode ?? null;
        $card_mode     = cardyFieCardMode($api_mode);
        $card_customer = $user->cardyfieCardCustomer->where('env', strtoupper($card_mode))->first();

        if(!$card_customer){;
            $message =  ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($message);
        }

        //check customer have on cardyfie platform or not
        $getCustomer = (new CardyFieHelper())->getCustomer( (string)$card_customer->ulid);

        if( $getCustomer['status'] == false){
            return $this->apiErrorHandle($getCustomer["message"]);
        }
        $data =[
            'card_customer' => $card_customer,
        ];
        $message =  ['success'=>[__('Data Fetch Successful')]];
        return Helpers::success($data,$message);

    }

    public function updateCustomer(Request $request){

        $validator = Validator::make($request->all(), [
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'date_of_birth'   => 'required|date',
            'identity_type'   => 'required|in:nid,passport,bvn',
            'identity_number' => 'required|string|max:50',
            'id_front_image'  => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'id_back_image'   => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'user_image'      => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'house_number'    => 'required|string|max:150',
            'city'            => 'required|string|max:100',
            'state'           => 'required|string|max:100',
            'zip_code'        => 'required|string|max:20',
            'address'         => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated  = $validator->validate();

        $user          = userGuard()['user'];
        $api_mode      = $this->api->config->cardyfie_mode ?? null;
        $card_mode     = cardyFieCardMode($api_mode);
        $card_customer = $user->cardyfieCardCustomer->where('env', strtoupper($card_mode))->first();
        if(!$card_customer){
            $error  = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

        try{

            if ($request->hasFile("id_front_image")) {
                $imageData = saveImageAndGetUrl($request->file("id_front_image"), 'card-kyc-images');
                $validated['id_front_image'] = $imageData['url'];
                $uploadedImages[] = $imageData['path'];
            }

            // id back image
            if ($request->hasFile("id_back_image")) {
                $imageData = saveImageAndGetUrl($request->file("id_back_image"), 'card-kyc-images');
                $validated['id_back_image'] = $imageData['url'];
                $uploadedImages[] = $imageData['path'];
            }

            // user image
            if ($request->hasFile("user_image")) {
                $imageData = saveImageAndGetUrl($request->file("user_image"), 'card-kyc-images');
                $validated['user_image'] = $imageData['url'];
                $uploadedImages[] = $imageData['path'];
            }


            $updateCustomer = (new CardyFieHelper())->updateCustomer($validated,(string)$card_customer->ulid);

            if( $updateCustomer['status'] == false){
                // delete all uploaded images if API failed
                foreach ($uploadedImages as $filePath) {
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                return $this->apiErrorHandle($updateCustomer["message"]);

            }
            try{
                if ($card_customer) {
                    $customerData = $updateCustomer['data']['customer'];
                    $customerData['id_front_image'] = isset($validated['id_front_image']) ? $validated['id_front_image'] :   $card_customer->id_front_image;
                    $customerData['id_back_image']  = isset($validated['id_back_image']) ? $validated['id_back_image'] :   $card_customer->id_back_image;
                    $customerData['user_image']     = isset($validated['user_image']) ? $validated['user_image'] :   $card_customer->user_image;

                    $card_customer->update(array_merge([
                        'user_type' => 'USER',
                        'user_id'   => $user->id,
                    ], $customerData));
                }

            }catch(Exception $e){
                $error  = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }

            $message =  ['success'=>[__('Customer has been updated successfully.')]];
            return Helpers::onlysuccess($message);

        }catch(Exception $e){
            $error  = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }

    public function cardBuy(Request $request){
        $validator = Validator::make($request->all(), [
            'name_on_card'  => 'required|string|min:4|max:50',
            'card_tier'     => 'required|string|max:30',
            'card_type'     => 'required|string|max:30',
            'currency'      => "required|string",
            'from_currency' => "required|string|exists:currencies,code",
        ]);

        if ($validator->fails()) {
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated  = $validator->validate();

        $user          = userGuard()['user'];
        $basic_setting = BasicSettings::first();


        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();

        if(!$wallet){
            $error  = ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);

        }

        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency){
            $error  = ['error'=>[__('Card Currency Not Found')]];
            return Helpers::error($error);
        }

        $cardCharge =  $this->cardCharges;
        $charges = $this->cardIssuesCharge($validated['card_tier'],$cardCharge,$wallet,$card_currency);


        if($charges['payable'] > $wallet->balance){
            $error  = ['error'=>[__("Your Wallet Balance Is Insufficient")]];
            return Helpers::error($error);
        }

        $card_mode = strtoupper(cardyFieCardMode($this->api_mode));
        $customer  = $user->cardyfieCardCustomer->where('env', $card_mode)->first();

        if(!$customer){
            $error  = ['error'=>[__("The customer doesn't create properly,Contact with owner")]];
            return Helpers::error($error);
        }
        if($customer->status != GlobalConst::CARD_CUSTOMER_APPROVED_STATUS){
            $error = ['error'=>[__("Wait until your customer status is approved. After that, you can create your card.")]];
            return Helpers::error($error);
        }


        $customer_card  = CardyfieVirtualCard::where('user_id',$user->id)->where('env',$card_mode)->count() ?? 0;
        if( $this->card_limit > 0 && $customer_card >= $this->card_limit){
            $error = ['error'=>[__("Sorry! You can not create more than")." ".$this->card_limit ." ".__("card using the same email address.")]];
            return Helpers::error($error);

        }
        $validated['customer_ulid'] = $customer->ulid;
        $validated['reference_id'] = "ref-".getTrxNum();

        DB::beginTransaction();
        try{
            //store card info to db
            $card_id = DB::table("cardyfie_virtual_cards")->insertGetId([
                'user_type'     => "USER",
                'user_id'       => $user->id,
                'reference_id'  => $validated['reference_id'],
                'customer_ulid' => $validated['customer_ulid'],
                'card_name'     => $validated['name_on_card'],
                'amount'        => floatVal(0),
                'currency'      => $validated['currency'],
                'card_tier'     => $validated['card_tier'],
                'card_type'     => $validated['card_type'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            //create card from cardyfie
            $createCard = (new CardyFieHelper())->issuesCard($validated);
            if( $createCard['status'] == false){
                return $this->apiErrorHandle($createCard["message"]);
            }


            //update card after success response;
            $card                = CardyfieVirtualCard::where('id',$card_id)->first();
            $card->ulid          = $createCard['data']['virtual_card']['ulid'] ?? null;
            $card->amount        = $createCard['data']['virtual_card']['card_balance'] ?? floatval(0);
            $card->card_exp_time = $createCard['data']['virtual_card']['card_exp_time'] ?? null;
            $card->masked_pan    = $createCard['data']['virtual_card']['masked_pan'] ?? null;
            $card->address       = $createCard['data']['virtual_card']['address'] ?? null;
            $card->status        = $createCard['data']['virtual_card']['status'] ?? null;
            $card->env           = $createCard['data']['virtual_card']['env'] ?? null;
            $card->save();


            $trx_id =  'CB'.getTrxNum();
            $sender = $this->insertCardBuy($trx_id,$user,$wallet,$card,$charges);
            $this->insertBuyCardCharge($charges,$user,$sender,$card->masked_pan);

            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                        'trx_id'         => $trx_id,
                        'title'          => "Virtual Card (Buy Card)",
                        'request_amount' => get_amount($card->amount,$charges['card_currency'],$charges['precision_digit']),
                        'payable'        => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                        'charges'        => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                        'card_amount'    => get_amount($card->amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'       => $card->masked_pan ?? "---- ----- ---- ----",
                        'status'         => $createCard['data']['virtual_card']['status'] ?? GlobalConst::CARD_PROCESSING_STATUS,
                    ];
                try{
                    $user->notify(new CreateMail($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }
            if( $basic_setting->sms_notification == true){
                try{
                    sendSms($user,'VIRTUAL_CARD_BUY',[
                        'request_amount'    => get_amount($card->amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'       => get_amount($card->amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'          => $card->masked_pan ?? "---- ----- ---- ----",
                        'trx'               => $trx_id,
                        'time'              => now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }
            //admin notification
            $this->adminNotification($trx_id,$charges,$user,$card);


            DB::commit();

            $message =  ['success'=>[__('Virtual Card Buy Successfully')]];
            return Helpers::onlysuccess($message);
        }catch(Exception $e){
            DB::rollBack();
            $error =  ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }

    public function cardDetails(){
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated  = $validator->validate();
        $user = userGuard()['user'];
        $myCard = CardyfieVirtualCard::where('user_id',$user->id)->where('ulid',$validated['card_id'])->first();
        if(!$myCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        $card_api = $get_card['data']['virtual_card'];

        $myCard = [
            "id"            => $myCard->id,
            "reference_id"  => $myCard->reference_id,
            "ulid"          => $myCard->ulid,
            "customer_ulid" => $myCard->customer_ulid,
            "card_name"     => $myCard->card_name,
            "amount"        => $myCard->amount,
            "currency"      => $myCard->currency,
            "card_tier"     => $myCard->card_tier,
            "card_type"     => $myCard->card_type,
            "card_exp_time" => $myCard->card_exp_time,
            "masked_pan"    => $myCard->masked_pan,
            "real_pan"      => $card_api['real_pan'] ?? $myCard->masked_pan,
            "cvv"           => $card_api['cvv'] ?? "***",
            "address"       => $myCard->address,
            "status"        => $myCard->status,
            "env"           => $myCard->env,
            "is_default"    => $myCard->is_default,
            "created_at"    => $myCard->created_at,
            "updated_at"    => $myCard->updated_at,
        ] ;

        $data =[
            'base_curr' => get_default_currency_code(),
            'myCard'=> (object)$myCard,
        ];
        $message =  ['success'=>[__('card Details')]];
        return Helpers::success($data,$message);
    }

    public function freezeUnfreeze(Request $request){

        $validator = Validator::make($request->all(), [
            'status'        => 'required|string',
            'card_id'       => 'required|string',
        ]);

        if ($validator->fails()) {
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validate();
        $card      = CardyfieVirtualCard::where('ulid',$validated['card_id'])->first();

        if(!$card){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $get_card  = (new CardyFieHelper())->getCard( (string)$card->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        if($validated['status'] == GlobalConst::CARD_ENABLED_STATUS){
            $make_unfreeze  = (new CardyFieHelper())->unfreeze( (string)$card->ulid);
            if($make_unfreeze['status'] == false){
                return $this->apiErrorHandle($make_unfreeze["message"], true);
            }
            $card->status =  GlobalConst::CARD_ENABLED_STATUS;
            $card->save();

            $message =  ['success'=>[__('Card unfreeze successfully')]];
            return Helpers::onlysuccess($message);

        }elseif($validated['status'] == GlobalConst::CARD_FREEZE_STATUS){
            $freeze  = (new CardyFieHelper())->freeze( (string)$card->ulid);
            if($freeze['status'] == false){
                return $this->apiErrorHandle($freeze["message"], true);
            }

            $card->status =  GlobalConst::CARD_FREEZE_STATUS;
            $card->save();

            $message =  ['success'=>[__('Card freeze successfully')]];
            return Helpers::onlysuccess($message);
        }
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
        $user = userGuard()['user'];
        $targetCard =  CardyfieVirtualCard::where('ulid',$validated['card_id'])->where('user_id',$user->id)->first();
        if(!$targetCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        };
        $withOutTargetCards =  CardyfieVirtualCard::where('id','!=',$targetCard->id)->where('user_id',$user->id)->get();
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

    public function cardDeposit(Request $request){
        $validator = Validator::make($request->all(), [
            'card_id'        => 'required',
            'deposit_amount' => 'required|numeric|gt:0',
            'currency'       => "required|string",
            'from_currency'  => "required|string|exists:currencies,code",
        ]);

        if ($validator->fails()) {
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated     = $validator->validate();
        $basic_setting = BasicSettings::first();
        $user          = userGuard()['user'];
        $amount        = $validated['deposit_amount'];

        $myCard =  CardyfieVirtualCard::where('user_id',$user->id)->where('ulid',$request->card_id)->first();
        if(!$myCard){
            $error =  ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet){
            $error =  ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }

        $cardCharge = $this->cardCharges;
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency){
            $error =  ['error'=>[__('Card Currency Not Found')]];
            return Helpers::error($error);
        }

        $charges = $this->cardDepositCharges($amount,$cardCharge,$wallet,$card_currency);


        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit){
            $error =  ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);
        }
        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED,null);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            $error =  ['error'=>[__($errorData['message'] ?? "Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

        if($charges['payable'] > $wallet->balance){
            $error =  ['error'=>[__("Your Wallet Balance Is Insufficient")]];
            return Helpers::error($error);
        }

        $deposit_request  = (new CardyFieHelper())->depositCard( (string) $myCard->ulid, $amount);

        if( $deposit_request['status'] == false){
            return $this->apiErrorHandle($deposit_request["message"]);
        }


        try{
            //after success
            $myCard->amount = $deposit_request['data']['virtual_card']['card_balance'];
            $myCard->save();

            //create local transaction
            $trx_id = 'CF'.getTrxNum();
            $sender = $this->insertCardFund($trx_id,$user,$wallet,$amount,$myCard,$charges);
            $this->insertFundCardCharge($charges,$user,$sender,$myCard->masked_pan,$amount);
            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'         => $trx_id,
                    'title'          => "Virtual Card (Fund Amount)",
                    'request_amount' => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'        => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                    'charges'        => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                    'card_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'card_pan'       => $myCard->masked_pan ?? "---- ----- ---- ----",
                    'status'         => "Success",
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
                        'card_pan'          =>  $myCard->masked_pan??"---- ----- ---- ----",
                        'trx'               => $trx_id,
                        'time'              =>  now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }

            //admin notification
            $this->adminNotificationFund($trx_id,$charges,$amount,$user,$myCard);

            $message =  ['success'=>[__('Card Funded Successfully')]];
            return Helpers::onlysuccess($message);
        }catch(Exception $e){
            $error =  ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }

    public function cardWithdraw(Request $request){

        $validator = Validator::make($request->all(), [
            'card_id'           => 'required',
            'withdraw_amount'   => 'required|numeric|gt:0',
            'currency'          => "required|string",
            'from_currency'     => "required|string|exists:currencies,code",
        ]);

        if ($validator->fails()) {
            $error  =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated     = $validator->validate();

        $basic_setting = BasicSettings::first();
        $user          = userGuard()['user'];
        $amount = $validated['withdraw_amount'];

        $myCard =  CardyfieVirtualCard::where('user_id',$user->id)->where('ulid',$request->card_id)->first();
        if(!$myCard){
            $error =  ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        //check real card balance
        if($amount > $get_card['data']['virtual_card']['card_balance']){
            $error =  ['error'=>[__("Your card doesn't have enough funds to complete this transaction")]];
            return Helpers::error($error);
        }

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet){
            $error =  ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }

        $cardCharge = $this->cardCharges;
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency){
            $error =  ['error'=>[__('Card Currency Not Found')]];
            return Helpers::error($error);
        }

        $charges = $this->cardWithdrawCharges($amount,$cardCharge,$wallet,$card_currency);


        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit){
            $error =  ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);
        }
        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED,null);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            $error =  ['error'=>[__($errorData['message'] ?? "Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }


        $withdraw_request  = (new CardyFieHelper())->withdrawCard( (string) $myCard->ulid, $amount);
        if( $withdraw_request['status'] == false){
            return $this->apiErrorHandle($withdraw_request["message"]);
        }

         if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        try{
            //after success
            $myCard->amount = $get_card['data']['virtual_card']['card_balance'];
            $myCard->save();


            //create local transaction
            $trx_id = 'CW-'.getTrxNum();
            $sender = $this->insertCardWithdrawal($trx_id,$user,$wallet,$amount,$myCard,$charges);
            $this->insertWithdrawalCardCharge($charges,$user,$sender,$myCard->masked_pan,$amount);
            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'         => $trx_id,
                    'title'          => "Virtual Card (Withdraw Amount)",
                    'request_amount' => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'        => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                    'charges'        => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                    'card_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'card_pan'       => $myCard->masked_pan ?? "---- ----- ---- ----",
                    'status'         => "Success",
                ];
                try{
                    $user->notify(new Withdraw($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }

            if( $basic_setting->sms_notification == true){
                try{
                    sendSms($user,'VIRTUAL_CARD_WITHDRAW',[
                        'request_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'       => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'          =>  $myCard->masked_pan??"---- ----- ---- ----",
                        'trx'               => $trx_id,
                        'time'              =>  now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }

            //admin notification
            $this->adminNotificationWithdraw($trx_id,$charges,$amount,$user,$myCard);

            $message =  ['success'=>[__('Card Withdrawal Successful')]];
            return Helpers::onlysuccess($message);
        }catch(Exception $e){
            $error =  ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

    }

    public function cardTransaction(){
        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated  = $validator->validate();
        $user = userGuard()['user'];
        $card = CardyfieVirtualCard::where('user_id',$user->id)->where('ulid',$validated['card_id'])->first();
        if(!$card){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $get_card_transactions  = (new CardyFieHelper())->getCardTransaction( (string) $card->ulid);

        if( $get_card_transactions['status'] == false){
            return $this->apiErrorHandle($get_card_transactions["message"]);
        }
        $get_card_transactions =  $get_card_transactions['data']['transactions'] ?? [];

        $data =[
            'base_curr' => get_default_currency_code(),
            'card_transactions'=> $get_card_transactions ?? [],
        ];
        $message =  ['success'=>[__('Virtual Card Transaction')]];
        return Helpers::success($data,$message);

    }

    public function closeCard(Request $request) {

        $validator = Validator::make(request()->all(), [
            'card_id'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated  = $validator->validate();
        $user       = userGuard()['user'];
        $targetCard = CardyfieVirtualCard::where('ulid',$validated['card_id'])->where('user_id',$user->id)->first();

        if(!$targetCard){
            $error = ['error'=>[__("Something is wrong in your card")]];
            return Helpers::error($error);
        }

        $make_close  = (new CardyFieHelper())->closeCard( (string)$targetCard->ulid);
        if($make_close['status'] == false){
            return $this->apiErrorHandle($make_close["message"]);
        }

        try{
            $targetCard->update([
                'status'         => GlobalConst::CARD_CLOSED_STATUS,
            ]);

        }catch(Exception $e) {
            $error =  ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }

        $message =  ['success'=>[__('Card closed successfully')]];
        return Helpers::onlysuccess($message);
    }


    //*****************************Card Buy Helpers******************************//
    //for card issues
    public function insertCardBuy($trx_id,$user,$wallet,$card,$charges) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details =[
            'card_info' => $card ?? '',
            'charges'   => $charges,
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $charges['card_amount'],
                'payable'                       => $charges['payable'],
                'available_balance'             => $afterCharge,
                'remark'                        => PaymentGatewayConst::CARDBUY,
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                        => true,
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

    public function insertBuyCardCharge($charges,$user,$id,$card_number) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $charges['percent_charge'],
                'fixed_charge'      => $charges['fixed_charge'],
                'total_charge'      => $charges['total_charge'],
                'created_at'        => now(),
            ]);


            //notification
            $notification_content = [
                'title'         =>__('buy Card'),
                'message'       => __('Buy card successful')." ".$card_number??"---- ---- ---- ----",
                'image'         => files_asset_path('user-profile'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_BUY,
                'user_id'   => $user->id,
                'message'   => $notification_content,
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
    public function adminNotification($trx_id,$charges,$user,$v_card){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Buy Card)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($v_card->amount,$charges['card_currency'],$charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Card Masked")." : ".$v_card->masked_pan ?? "---- ----- ---- ----"."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Virtual Card (Buy Card)")." (".userGuard()['type'].")",
            'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($v_card->amount,$charges['card_currency'],$charges['precision_digit'])." ".__("Card Masked")." : ".$v_card->masked_pan ??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::CARD_BUY,
            'admin_db_title' => "Virtual Card Buy"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($v_card->amount,$charges['card_currency'],$charges['precision_digit']).","."Card Masked"." : ".$v_card->masked_pan ?? "---- ----- ---- ----"." (".$user->email.")",
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


    // for deposit
    public function insertCardFund($trx_id,$user,$wallet,$amount,$myCard,$charges) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details =[
            'card_info' =>   $myCard ?? '',
            'charges'   =>   $charges,
        ];
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
                'status'                        => true,
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
                'title'         =>__("Deposit Amount"),
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

    public function adminNotificationFund($trx_id,$charges,$amount,$user,$myCard){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Fund Amount)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Card Masked")." : ".$myCard->masked_pan??"---- ----- ---- ----"."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Virtual Card (Fund Amount)")." (".userGuard()['type'].")",
            'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])." ".__("Card Masked")." : ".$myCard->masked_pan??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::CARD_FUND,
            'admin_db_title' => "Virtual Card Funded"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit']).","."Card Masked"." : ".$myCard->masked_pan ?? "---- ----- ---- ----"." (".$user->email.")",
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

    //for withdraw
    public function insertCardWithdrawal($trx_id,$user,$wallet,$amount,$myCard,$charges) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance + $charges['payable']);
        $details =[
            'card_info' =>   $myCard ?? '',
            'charges'   =>   $charges,
        ];
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
                'remark'                        => ucwords(PaymentGatewayConst::CARDWITHDRAW),
                'details'                       => json_encode($details),
                'attribute'                     => PaymentGatewayConst::RECEIVED,
                'status'                        => true,
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

    public function adminNotificationWithdraw($trx_id,$charges,$amount,$user,$myCard){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Withdraw Amount)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Will Receive")." : ".get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit'])."<br>".__("Card Masked")." : ".$myCard->masked_pan??"---- ----- ---- ----"."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Virtual Card (Withdraw Amount)")." (".userGuard()['type'].")",
            'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit'])." ".__("Card Masked")." : ".$myCard->masked_pan??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::WITHDRAWMONEY,
            'admin_db_title' => "Virtual Card Withdrawal"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,$charges['card_currency'],$charges['precision_digit']).","."Card Masked"." : ".$myCard->masked_pan ?? "---- ----- ---- ----"." (".$user->email.")",
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


    public function updateSenderWalletBalance($authWallet,$afterCharge) {
        $authWallet->update([
            'balance'   => $afterCharge,
        ]);
    }
    //charge calculation functions
    public function cardIssuesCharge($card_tier,$charges,$wallet,$card_currency){
        if($card_tier == GlobalConst::UNIVERSAL_TIER){
            $card_issues_fixed_fee = $charges->universal_card_issues_fee;
        }else{
            $card_issues_fixed_fee = $charges->platinum_card_issues_fee;
        }
        $sPrecision = get_wallet_precision($wallet->currency);
        $exchange_rate = $wallet->currency->rate/$card_currency->rate;

        $data['exchange_rate']         = $exchange_rate;
        $data['card_amount']           = floatval(0);
        $data['card_currency']         = $card_currency->code;
        $data['card_currency_rate']    = $card_currency->rate;

        $data['from_amount']           = $data['card_amount'] * $exchange_rate;
        $data['from_currency']         = $wallet->currency->code;
        $data['from_currency_rate']    = $wallet->currency->rate;

        $data['percent_charge']        = floatval(0);
        $data['fixed_charge']          = ($card_issues_fixed_fee * $exchange_rate);
        $data['total_charge']          = $data['percent_charge'] + $data['fixed_charge'];
        $data['from_wallet_balance']   = $wallet->balance;
        $data['payable']               = $data['from_amount'] + $data['total_charge'];
        $data['card_platform']         = "CardyFie";
        $data['precision_digit']       = $sPrecision;


        return $data;

    }

    public function cardDepositCharges($amount,$charges,$wallet,$card_currency){
        $sPrecision = get_wallet_precision($wallet->currency);
        $exchange_rate = $wallet->currency->rate/$card_currency->rate;

        $data['exchange_rate']         = $exchange_rate;
        $data['card_amount']           = $amount;
        $data['card_currency']         = $card_currency->code;
        $data['card_currency_rate']    = $card_currency->rate;

        $data['from_amount']           = $amount * $exchange_rate;
        $data['from_currency']         = $wallet->currency->code;
        $data['from_currency_rate']    = $wallet->currency->rate;

        $data['percent_charge']        = (($data['card_amount'] *  $data['exchange_rate']) / 100) * $charges->card_deposit_percent_fee ?? 0;
        $data['fixed_charge']          = ($charges->card_deposit_fixed_fee * $exchange_rate);
        $data['total_charge']          = $data['percent_charge'] + $data['fixed_charge'];
        $data['from_wallet_balance']   = $wallet->balance;
        $data['payable']               = $data['from_amount'] + $data['total_charge'];
        $data['card_platform']         = "CardyFie";
        $data['precision_digit']       = $sPrecision;

        return $data;

    }

    public function cardWithdrawCharges($amount,$charges,$wallet,$card_currency){
        $sPrecision = get_wallet_precision($wallet->currency);
        $exchange_rate = $wallet->currency->rate/$card_currency->rate;

        $data['exchange_rate']         = $exchange_rate;
        $data['card_amount']           = $amount;
        $data['card_currency']         = $card_currency->code;
        $data['card_currency_rate']    = $card_currency->rate;

        $data['from_amount']           = $amount * $exchange_rate;
        $data['from_currency']         = $wallet->currency->code;
        $data['from_currency_rate']    = $wallet->currency->rate;

        $data['percent_charge']        = (($data['card_amount'] *  $data['exchange_rate']) / 100) * $charges->card_withdraw_percent_fee ?? 0;
        $data['fixed_charge']          = ($charges->card_withdraw_fixed_fee * $exchange_rate);
        $data['total_charge']          = $data['percent_charge'] + $data['fixed_charge'];
        $data['from_wallet_balance']   = $wallet->balance;
        $data['payable']               = $data['from_amount'] - $data['total_charge'];
        $data['card_platform']         = "CardyFie";
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


}
