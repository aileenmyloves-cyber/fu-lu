<?php

namespace App\Http\Controllers\User;

use App\Constants\GlobalConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use App\Http\Helpers\Response;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StrowalletVirtualController extends Controller
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
        $page_title    = __("Virtual Card");
        $user          = auth()->user();
        $myCards       = StrowalletVirtualCard::where('user_id', $user->id)->latest()->limit($this->card_limit)->get();
        $customer      = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        $customer_card = StrowalletVirtualCard::where('user_id',$user->id)->count();
        $transactions  = Transaction::auth()->virtualCard()->latest()->take(5)->get();
        $cardApi = $this->api;

        return view('user.sections.virtual-card-strowallet.index',compact(
            'page_title',
            'cardApi',
            'myCards',
            'transactions',
            'customer_card',
            'customer'

        ));
    }
    /**
     * Method for card details
     * @param $card_id
     * @param \Illuminate\Http\Request $request
     */
    public function cardDetails($card_id){
        $page_title = __("Card Details");
        $user       = auth()->user();
        $myCard     = StrowalletVirtualCard::where('user_id', $user->id)->where('card_id', $card_id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        if($myCard->card_status == StrowalletVirtualCard::STRING_STATUS_PENDING || $myCard->card_status == StrowalletVirtualCard::STRING_STATUS_PROCESSING){
            $card_details   = card_details($card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

            if($card_details['status'] == false){
                return back()->with(['error' => parse_error($card_details, __("Something is wrong in your card"))]);
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
        $cardApi = $this->api;
        return view('user.sections.virtual-card-strowallet.details',compact(
            'page_title',
            'myCard',
            'cardApi'
        ));
    }

     /**
     * Method for strowallet create customer page
     */
    public function createCustomer(){
        $user = userGuard()['user'];
        $page_title = __("Create Card Holder");
        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        if($customer_kyc){
            return redirect()->route('user.strowallet.virtual.card.edit.customer');
        }
        return view('user.sections.virtual-card-strowallet.customer.create',compact('page_title','user'));
    }
    /**
     * Method for strowallet create customer
     */
    public function storeCustomer(Request $request){

        $user         = userGuard()['user'];
        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();

        if($customer_kyc && !empty($customer_kyc->customer_id)){
            return back()->with(['error' => [__('Card holder information can not be changed after it is verified.')]]);
        }

        if(!$customer_kyc || $customer_kyc->id_image == null){
            $id_image_validate_rules = "required|image|mimes:jpg,png,svg,webp|max:10240";
        }else{
            $id_image_validate_rules = "nullable|image|mimes:jpg,png,svg,webp|max:10240";
        }

        $validated = Validator::make($request->all(),[
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
        ])->validate();

        $validated['phone_code'] = remove_special_char($validated['phone_code']);

        $old_image = $customer_kyc->id_image ?? null;
        $new_image = null;

        if($request->hasFile('id_image')) {
            $kyc_image = get_files_from_fileholder($request,'id_image');
            // Keep the old file until the API call succeeds, so a rollback leaves it intact
            $new_image = upload_files_from_path_dynamic($kyc_image,'card-kyc-images',null);
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
                return back()->withInput()->with(['error' => parse_error($api_customer, __("Something went wrong! Please try again."))]);
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

            return redirect()->route("user.strowallet.virtual.card.create")->with(['success' => [__('Customer has been created successfully.')]]);

        }catch(Exception $e){
            DB::rollBack();
            return back()->withInput()->with(['error' => [$e->getMessage() ?: __("Something went wrong! Please try again.")]]);
        }

    }
    /**
     * Method for strowallet edit customer
     */
    public function editCustomer(){
        $user = userGuard()['user'];
        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        $page_title = !empty($customer_kyc->customer_id) ? __("Card Holder Details") : __("Update Card Holder");
        return view('user.sections.virtual-card-strowallet.customer.edit',compact('page_title','user','customer_kyc'));
    }

    /**
     * Method for strowallet card buy page
     */
    public function createPage(){
        $page_title = __("Create Virtual Card");
        $supported_currency = support_currencies(['USD']);
        $from_wallets = UserWallet::auth()->whereHas('currency',function($q) {
            $q->where("status",GlobalConst::ACTIVE);
        })->active()->get();
        $user       = userGuard()['user'];
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();

        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        if(!$customer_kyc){
            return redirect()->route('user.strowallet.virtual.card.create.customer')->with(['error' => [__('A cardholder is required before creating a card. Please create a cardholder first.')]]);
        }

        return view('user.sections.virtual-card-strowallet.create',compact('page_title','user','cardCharge','supported_currency','from_wallets'));
    }

    /**
     * Method for strowallet card buy
     */
    public function cardBuy(Request $request){
        $user = userGuard()['user'];
        $request->validate([
            'card_amount'   => 'required|numeric|gt:0',
            'currency'      => "required|string|exists:currencies,code",
            'from_currency' => "required|string|exists:currencies,code",
            'pin'           => $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ]);

        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        if(!$customer_kyc){
            return redirect()->route('user.strowallet.virtual.card.create.customer')->with(['error' => [__('A cardholder is required before creating a card. Please create a cardholder first.')]]);
        }
        if(empty($customer_kyc->customer_id)){
            return redirect()->route('user.strowallet.virtual.card.edit.customer')->with(['error' => [__('Your cardholder is not verified yet. Please update the cardholder information and try again.')]]);
        }

        $formData   = $request->all();
        $kycData    = Arr::except($customer_kyc->toArray(), ['id', 'user_id', 'id_image', 'face_image', 'created_at', 'updated_at','faceImageData']);
        $formData   = array_merge($formData, $kycData);

        //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$formData['pin']);
            if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
        }

        $amount = $request->card_amount;
        $currency = $formData['currency'];
        $basic_setting = BasicSettings::first();

        $supported_currency = ['USD'];
        if( !in_array($currency,$supported_currency??[])) return back()->with(['error' => [$currency." ". __("Currency isn't supported for creating virtual card, Please contact")]]);

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($formData) {
            $q->where("code",$formData['from_currency'])->active();
        })->active()->first();
        if(!$wallet) return back()->with(['error' => [__('User wallet not found')]]);

        $card_currency = Currency::active()->where('code',$formData['currency'])->first();
        if(!$card_currency) return back()->with(['error' => [__('Card Currency Not Found')]]);

        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();
        $charges = $this->cardCharges($formData['card_amount'],$cardCharge,$wallet,$card_currency);

        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit)  return back()->with(['error' => [__("Please follow the transaction limit")]]);

        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED);
        }catch(Exception $e){
           $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again."))]]);
        }

        if($charges['payable'] > $wallet->balance) return back()->with(['error' => [__("Your Wallet Balance Is Insufficient")]]);

        $customer_card  = StrowalletVirtualCard::where('user_id',$user->id)->count();
        if($customer_card >= $this->card_limit){
            return back()->with(['error' => [__("Sorry! You can not create more than")." ".$this->card_limit ." ".__("card using the same email address.")]]);
        }


        $created_card = create_strowallet_virtual_card($this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$formData);

        if($created_card['status'] == false){
            return back()->with(['error' => parse_error($created_card, __("Card creation failed. Please try again."))]);
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
        $strowallet_card->status            = $created_card['data']['card_status'] == StrowalletVirtualCard::STRING_STATUS_COMPLETED ? true : false ;
        $strowallet_card->is_active         = $created_card['data']['card_status'] == StrowalletVirtualCard::STRING_STATUS_COMPLETED ? true : false;
        $strowallet_card->save();

        $trx_id =  'CB'.getTrxNum();
        try{
            $sender = $this->insertCardBuy($trx_id,$user,$wallet,$amount,$strowallet_card,$charges,$created_card['data']);
            $this->insertBuyCardCharge($charges,$user,$sender,$strowallet_card->card_number);
            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'            => $trx_id,
                    'title'             => "Virtual Card (Buy Card)",
                    'request_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'           => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
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
            return redirect()->route("user.strowallet.virtual.card.index")->with(['success' => [__('Virtual Card Buy Successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
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
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $charges['payable'],
                'available_balance'             => $afterCharge,
                'remark'                        => PaymentGatewayConst::CARDBUY,
                'details'                       => json_encode($details),
                'attribute'                     => PaymentGatewayConst::RECEIVED,
                'status'                        => true,
                'callback_ref'                  => $api_response['card_id'] ?? null,
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
                    (new PushNotificationHelper())->prepare([$user->id],[
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
     * card freeze unfreeze
     */
    public function cardBlockUnBlock(Request $request) {

        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->safe()->all();
        if($request->status == 1){

            $card       = StrowalletVirtualCard::where('id',$request->data_target)->where('is_active',1)->first();
            $data       = change_strowallet_card_status($card->card_id,'frozen',$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

            if($data['status'] == true) {
                $card->is_active = 0;
                $card->save();
                $success = ['success' => [__('Card Freeze successfully')]];
                return Response::success($success,null,200);
            }else{
                $error = ['error' =>  [$data['message'] ?: __("Something went wrong! Please try again.")]];
                return Response::error($error,null,400);
            }
        }else{

            $card   = StrowalletVirtualCard::where('id',$request->data_target)->where('is_active',0)->first();
            $data   = change_strowallet_card_status($card->card_id,'active',$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

            if($data['status'] == true){
                $card->is_active = 1;
                $card->save();
                $success = ['success' => [__('Card UnFreeze successfully')]];
                return Response::success($success,null,200);
            }else{
                $error = ['error' =>  [$data['message'] ?: __("Something went wrong! Please try again.")]];
                return Response::error($error,null,400);
            }
        }

    }

    public function makeDefaultOrRemove(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  StrowalletVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();
        $withOutTargetCards =  StrowalletVirtualCard::where('id','!=',$validated['target'])->where('user_id',$user->id)->get();

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

        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return back()->with(['success' => [__('Status Updated Successfully!')]]);
    }
    /**
     * Card Fund
     */
    public function cardFundPage($id){
        $page_title = __("Fund Virtual Card");
        $from_wallets = UserWallet::auth()->whereHas('currency',function($q) {
            $q->where("status",GlobalConst::ACTIVE);
        })->active()->get();
        $user       = userGuard()['user'];
        $cardReloadCharge = TransactionSetting::where('slug','reload_card')->where('status',1)->first();
        $myCard = StrowalletVirtualCard::where('user_id', auth()->user()->id)->where('id',$id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $supported_currency = support_currencies([$myCard->currency ?? "USD"]);

        return view('user.sections.virtual-card-strowallet.fund',compact('page_title','user','cardReloadCharge','supported_currency','from_wallets','myCard'));
    }
    public function cardFundConfirm(Request $request){
        $validated = Validator::make($request->all(),[
            'id'            => 'required|integer',
            'fund_amount'   => 'required|numeric|gt:0',
            'currency'      => "required|string|exists:currencies,code",
            'from_currency' => "required|string|exists:currencies,code",
            'pin'           =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ])->validate();

        $basic_setting = BasicSettings::first();
        $user = userGuard()['user'];

        //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
        }

        $amount = $request->fund_amount;

        $myCard =  StrowalletVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();

        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet) return back()->with(['error' => [__('User wallet not found')]]);

        $cardCharge = TransactionSetting::where('slug','reload_card')->where('status',1)->first();
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency) return back()->with(['error' => [__('Card Currency Not Found')]]);

        $charges = $this->cardCharges($validated['fund_amount'],$cardCharge,$wallet,$card_currency);
        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit)  return back()->with(['error' => [__("Please follow the transaction limit")]]);
        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED);
        }catch(Exception $e){
            $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again."))]]);
        }
        if($charges['payable'] > $wallet->balance) return back()->with(['error' => [__("Your Wallet Balance Is Insufficient")]]);

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
            return redirect()->route("user.strowallet.virtual.card.index")->with(['success' => [__('Card Funded Successfully')]]);

        }else{
            return redirect()->back()->with(['error' => [@$decodedResult['message'].' ,'.__('Please Contact With Administration.')]]);
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
                'user_id'                     => $user->id,
                'user_wallet_id'              => $authWallet->id,
                'payment_gateway_currency_id' => null,
                'type'                        => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                      => $trx_id,
                'request_amount'              => $amount,
                'payable'                     => $charges['payable'],
                'available_balance'           => $afterCharge,
                'remark'                      => ucwords(PaymentGatewayConst::CARDFUND),
                'details'                     => json_encode($details),
                'attribute'                   => PaymentGatewayConst::RECEIVED,
                'status'                      => $status,
                'callback_ref'                => $card_response['reference'],
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

    /**
     * Card Withdraw Controller
     */
    public function cardWithdraw($card_id){
        $page_title = __("Withdraw Funds from Virtual Card");
        $from_wallets = UserWallet::auth()->whereHas('currency',function($q) {
            $q->where("status",GlobalConst::ACTIVE);
        })->active()->get();

        $user       = userGuard()['user'];
        $cardCharges = TransactionSetting::where('slug','withdraw_card')->where('status',1)->first();

        $myCard = StrowalletVirtualCard::where('user_id', auth()->user()->id)->where('card_id',$card_id)->first();

        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $card_details   = card_details($card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

        if($card_details['status'] == false){
            return back()->with(['error' => parse_error($card_details, __("Something is wrong in your card"))]);
        }
        $supported_currency = support_currencies([$myCard->currency ?? "USD"]);

        return view('user.sections.virtual-card-strowallet.withdraw',compact('page_title','user','cardCharges','supported_currency','from_wallets','myCard'));
    }
    public function cardWithdrawConfirm(Request $request){
        $validated = Validator::make($request->all(),[
            'id'              => 'required|integer',
            'withdraw_amount' => 'required|numeric|gt:0',
            'currency'        => "required|string",
            'from_currency'   => "required|string|exists:currencies,code",
            'pin'               =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ])->validate();

        $basic_setting = BasicSettings::first();


        $mode          = $this->api->config->strowallet_mode ?? GlobalConst::SANDBOX;
        $user          = userGuard()['user'];
        $amount        = $validated['withdraw_amount'];

        //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
        }

        $myCard =  StrowalletVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $get_card  =  card_details($myCard->card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);
        if($get_card['status'] == false){
            return back()->with(['error' => parse_error($get_card, __("Something is wrong in your card"))]);
        }

        // check real card balance
        if($mode != GlobalConst::SANDBOX){
            if($amount > $get_card['data']['card_detail']['balance']) return back()->with(['error' => [__("Your card doesn't have enough funds to complete this transaction")]]);
        }

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet) return back()->with(['error' => [__('User wallet not found')]]);

        $cardCharge = TransactionSetting::where('slug','withdraw_card')->where('status',1)->first();
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency) return back()->with(['error' => [__('Card Currency Not Found')]]);

        $charges = $this->cardWithdrawCharges($amount,$cardCharge,$wallet,$card_currency);

        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit)  return back()->with(['error' => [__("Please follow the transaction limit")]]);
        //daily and monthly

        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED);
        }catch(Exception $e){
           $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again."))]]);
        }

        $withdraw_request = withdraw_virtual_card($this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$myCard->card_id, $amount);
        if($withdraw_request['status'] == false){
            return back()->with(['error' => parse_error($withdraw_request, __("Something is wrong in your card"))]);
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
                        'request_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'       => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'          =>  $myCard->card_number??"---- ----- ---- ----",
                        'trx'               => $trx_id,
                        'time'              =>  now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }

            //admin notification
            $this->adminNotificationWithdraw($trx_id,$charges,$amount,$user,$myCard);

             return redirect()->route("user.strowallet.virtual.card.index")->with(['success' => [__('Card Withdrawal Successful')]]);
        }catch(Exception $e){
            return redirect()->back()->with(['error' => [__("Something went wrong! Please try again.")]]);
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

     /**
     * Transactions
     */
    public function cardTransaction($card_id) {
        $user = auth()->user();
        $card = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id', $card_id)->first();
        $page_title = __("Virtual Card Transaction");
        if(!$card) return back()->with(['error' => [__("Something is wrong in your card")]]);

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

        if( isset($result['success']) && $result['success'] == true ){
            $data =[
                'status'        => true,
                'message'       => "Card Details Retrieved Successfully.",
                'data'          => $result['response'],
            ];
        }else{
            $data =[
                'status'        => false,
                'message'       => $result['message'] ?? 'Something is wrong! Contact With Admin',
                'data'          => null,
            ];
        }
        if($data['status'] == false){
            return back()->with(['error' => parse_error($data, __("Something is wrong in your card"))]);
        }


        return view('user.sections.virtual-card-strowallet.trx',compact('page_title','card','data'));


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

        $data['percent_charge']        = ($data['from_amount'] / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge']          = $exchange_rate * $charges->fixed_charge ?? 0;
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
        $data['card_currency']         = $card_currency->code;
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
        return back()->with(['error' => [$errorString ?? __("Something went wrong! Please try again.")]]);

    }

    public function webhookTransaction($card_id){
        $page_title = __("Webhook Logs");
        $user = userGuard()['user'];
        $data = StrowalletWebhookData::where('user_id',$user->id)->where('cardId',$card_id)->latest()->get();
        return view('user.sections.virtual-card-strowallet.webhook-trx',compact('page_title','data'));
    }


    public function getWebhookData(Request $request)
    {

        $response_data = $request->all();
        logger('StroWallet Webhook Received', [$response_data]);
        if (empty($response_data['event'])) {
            return false;
        }
        try{
            $event = $response_data['event'];

            if($event == "virtualcard.created.complete"){
                return $this->virtual_card_creation_webhook($response_data);
            }elseif($event == "virtualcard.created.failed"){
                return $this->virtual_card_creation_failed_webhook($response_data);
            }elseif($event == "virtualcard.transaction.declined" || $event == "virtualcard.transaction.declined.terminated" || $event == "virtualcard.transaction.crossborder" ){
                return $this->card_using_webhook($response_data);
            }elseif($event == "virtualcard.topup.complete"){
                return $this->virtual_card_topup_webhook($response_data);
            }elseif($event == "virtualcard.topup.failed"){
                return $this->virtual_card_topup_failed_webhook($response_data);
            }elseif($event == "virtualcard.withdrawal.success"){
                return $this->virtual_card_withdraw_webhook($response_data);
            }elseif($event == "otp.code"){
                return $this->virtual_card_auth_code_webhook($response_data);
            }

        }catch(Exception $e){
            Log::error('Webhook Handling Error: ' . $e->getMessage());
            return false;
        }
    }

    public function virtual_card_creation_webhook($response){
        $cardId = $response['cardId'] ?? null;
        $card = StrowalletVirtualCard::where('card_id', $cardId)->first();
         if($card && $response['createdStatus'] == "completed") {
            DB::beginTransaction();
            try{
                $card_details   = card_details($card->card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);
                $card->card_name      = $card_details['data']['card_detail']['card_name'];
                $card->card_status    = $card_details['data']['card_detail']['card_status'];
                $card->card_number    = $card_details['data']['card_detail']['card_number'];
                $card->last4          = $card_details['data']['card_detail']['last4'];
                $card->cvv            = $card_details['data']['card_detail']['cvv'];
                $card->expiry         = $card_details['data']['card_detail']['expiry'];
                $card->balance        = $card_details['data']['card_detail']['balance'];
                $card->customer_email = $card_details['data']['card_detail']['customer_email'];
                $card->status         = $card_details['data']['card_detail']['card_status'] == StrowalletVirtualCard::STRING_STATUS_ACTIVE ? true :  false ;
                $card->is_active      = $card_details['data']['card_detail']['card_status'] == StrowalletVirtualCard::STRING_STATUS_ACTIVE ? true :  false ;
                $card->save();

                Log::info("Card ID {$cardId} marked active via webhook.");
                DB::commit();

            }catch (Exception $e) {
                DB::rollBack();
                Log::error('Webhook Handling Error: ' . $e->getMessage());
                return false;
            }

         }else{
            Log::error('Card Creation Failed Event Status: ' . $response['createdStatus']);
            return false;
         }

    }

    public function virtual_card_creation_failed_webhook($response){
        $cardId = $response['cardId'] ?? null;
        $card = StrowalletVirtualCard::where('card_id', $cardId)->first();

        if(!$card || ($response['status'] ?? null) !== "failed") {
            Log::error('Card not found, Event Status: ' . ($response['status'] ?? 'unknown'));
            return false;
        }

        $transaction = Transaction::where('user_id', $card->user_id)
            ->where('callback_ref', $card->card_id)
            ->first();

        if (!$transaction) {
            Log::error("Transaction not found — User ID: {$card->user_id}, Ref: " . ($response['reference'] ?? 'N/A'));
            return false;
        }

        $refund_wallet = UserWallet::where('user_id', $card->user_id)->whereHas("currency",function($q) use ($card) {
            $q->where("code",$card->currency)->active();
        })->active()->first();

        if(!$refund_wallet){
            Log::error("User Wallet not found to refund balance — Card ID: {$card->card_id}, Event: " . ($response['event'] ?? 'N/A'));
            return false;
        }

        DB::beginTransaction();
        try{

            //fund refund
            $refund_wallet->increment('balance', $transaction->payable ?? 0);

            //transaction update
            $transaction->status            = PaymentGatewayConst::STATUSFAILD;
            $transaction->available_balance = $refund_wallet->balance;
            $transaction->save();

            //card status update
            $card->card_status = StrowalletVirtualCard::STRING_STATUS_FAILED;
            $card->status      = 0;
            $card->is_active   = 0;
            $card->save();

            DB::commit();
            Log::info("Card ID {$cardId} marked as failed and refunded via webhook.");

        }catch (Exception $e) {
            DB::rollBack();
            Log::error('Webhook Handling Error: ' . $e->getMessage());
            return false;
        }
    }

    public function card_using_webhook($response){
        $card = StrowalletVirtualCard::where('card_id',$response['cardId'])->first();

        if($card){
            DB::beginTransaction();
            try{
                //store data
                $st['parent_id']        =  $card->id;
                $st['user_id']          =  $card->user_id;
                $st['transaction_id']   =  $response['id'];
                $st['event']            =  $response['event'];
                $st['cardId']           =  $response['cardId'];
                $st['card_currency']    =  $card->currency ?? "USD";
                $st['data']             =  $response ?? [];

                StrowalletWebhookData::create($st);

                if($response['event'] == "virtualcard.transaction.declined.terminated"){
                    $refund_wallet = UserWallet::where('user_id', $card->user_id)->whereHas("currency",function($q) use ($card) {
                        $q->where("code",$card->currency)->active();
                    })->active()->first();

                    if(!$refund_wallet){
                        logger("User Wallet not found to refund balance — Card ID: {$card->card_id}, Event: {$response['event']}");
                        return;
                    }

                    $refund_wallet->increment('balance', $response['amount'] ?? 0);
                }

                logger("Webhook Data Created Successfully! Status: " . $response['event']);
                DB::commit();

            }catch (Exception $e) {
                DB::rollBack();
                Log::error('Webhook Handling Error: ' . $e->getMessage());
                return false;
            }
        }else{
            logger("Webhook Data Created Failed!");
            return;
        }

    }

    public function virtual_card_topup_webhook($response){
        $cardId = $response['cardId'] ?? null;
        $card = StrowalletVirtualCard::where('card_id', $cardId)->first();
        if($card && $response['status'] == "complete") {

            DB::beginTransaction();
            try{
                $card_details = card_details(
                    $card->card_id,
                    $this->api->config->strowallet_public_key,
                    $this->api->config->strowallet_url
                );

                if (!isset($card_details['data']['card_detail']['balance'])) {
                    logger("Card details missing balance — Card ID: {$card->card_id}");
                    return;
                }

                $card->balance = $card_details['data']['card_detail']['balance'];
                $card->save();


                $transaction = Transaction::where('user_id', $card->user_id)->whereNot('status', 1)
                    ->where('callback_ref', $response['reference'])
                    ->first();

                if (!$transaction) {
                    logger("Transaction not found — User ID: {$card->user_id}, Ref: {$response['reference']}");
                    return;
                }

                $transaction->status = true;
                $transaction->save();

                Log::info("Card ID {$cardId}, is funded via webhook.");
                DB::commit();

            }catch (Exception $e) {
                DB::rollBack();
                Log::error('Webhook Handling Error: ' . $e->getMessage());
                return false;
            }
        }else{
            Log::error('Card Fund Failed, Event Status: ' . $response['status']);
            return false;
         }


    }

    public function virtual_card_topup_failed_webhook($response){
        if($response['status'] == "failed") {
            $transaction = Transaction::whereNot('status', 1)
                    ->where('callback_ref', $response['reference'])
                    ->first();

            if (!$transaction) {
                logger("Transaction not found Ref: {$response['reference']}");
                return;
            }

            $refund_wallet = UserWallet::where('id', $transaction->user_wallet_id)->active()->first();

            if(!$refund_wallet){
                Log::error("User Wallet not found to refund balance, Event: " . ($response['event'] ?? 'N/A'));
                return false;
            }

            DB::beginTransaction();
            try{
                //fund refund
                $refund_wallet->increment('balance', $transaction->payable ?? 0);

                $transaction->status            = PaymentGatewayConst::STATUSFAILD;
                $transaction->available_balance = $refund_wallet->balance;
                $transaction->save();

                Log::info("Card Fund Failed.");
                DB::commit();

            }catch (Exception $e) {
                DB::rollBack();
                Log::error('Webhook Handling Error: ' . $e->getMessage());
                return false;
            }
        }else{
            Log::error('Card Fund Failed, Event Status: ' . $response['status']);
            return false;
         }


    }

    public function virtual_card_withdraw_webhook($response){
        $cardId = $response['cardId'] ?? null;
        $card = StrowalletVirtualCard::where('card_id', $cardId)->first();
        if($card && $response['status'] == "complete") {
            DB::beginTransaction();
            try{
                $card_details = card_details(
                    $card->card_id,
                    $this->api->config->strowallet_public_key,
                    $this->api->config->strowallet_url
                );

                if (!isset($card_details['data']['card_detail']['balance'])) {
                    logger("Card details missing balance — Card ID: {$card->card_id}");
                    return;
                }

                $card->balance = $card_details['data']['card_detail']['balance'];
                $card->save();


                $transaction = Transaction::where('user_id', $card->user_id)->whereNot('status', 1)
                    ->where('callback_ref', $response['reference'])
                    ->first();

                if (!$transaction) {
                    logger("Transaction not found — User ID: {$card->user_id}, Ref: {$response['reference']}");
                    return;
                }

                $transaction->status = true;
                $transaction->save();

                //update wallet
                $refund_wallet = UserWallet::where('user_id', $card->user_id)->whereHas("currency",function($q) use ($card) {
                    $q->where("code",$card->currency)->active();
                })->active()->first();

                if(!$refund_wallet){
                    logger("User Wallet not found to refund balance — Card ID: {$card->card_id}, Event: {$response['event']}");
                    return;
                }

                $refund_wallet->increment('balance', $response['credited'] ?? 0);

                Log::info("Card ID {$cardId}, is withdrawal via webhook.");

                DB::commit();
            }catch (Exception $e) {
                DB::rollBack();
                Log::error('Webhook Handling Error: ' . $e->getMessage());
                return false;
            }
        }else{
            Log::error('Card Fund Failed, Event Status: ' . $response['status']);
            return false;
         }


    }

    public function virtual_card_auth_code_webhook($response){
        $cardId = $response['cardId'] ?? null;
        $card = StrowalletVirtualCard::where('card_id', $cardId)->first();

         if($card && $response['createdStatus'] == "completed") {
            DB::beginTransaction();
            try{

                $card->authorization_code = $response['authorizationCode'];
                $card->auth_code_send_at  = now();
                $card->save();

                Log::info("Card ID {$cardId} authorization code.");
                DB::commit();

            }catch (Exception $e) {
                DB::rollBack();
                Log::error('Webhook Handling Error: ' . $e->getMessage());
                return false;
            }

         }else{
            Log::error('Card Authorization Code Failed Event Status: ' . $response['createdStatus']);
            return false;
         }

    }

}
