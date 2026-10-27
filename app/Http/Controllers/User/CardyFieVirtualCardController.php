<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Admin\Currency;
use App\Models\VirtualCardApi;
use App\Models\UserNotification;
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

    public function __construct()
    {
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
    public function index()
    {
        $page_title     = __("Virtual Card");
        $user           = auth()->user();
        $card_mode = strtoupper(cardyFieCardMode($this->api_mode));
        $query = CardyfieVirtualCard::where('user_id', $user->id)->where('env', $card_mode)->latest();
        if ($this->card_limit > 0) {
            $query->limit($this->card_limit);
        }
        $myCards = $query->get();


        $customer_card = CardyfieVirtualCard::where('user_id',$user->id)->where('env',$card_mode)->count() ?? 0;
        $transactions   = Transaction::auth()->virtualCard()->latest()->take(5)->get();
        $cardApi = $this->api;

       return view('user.sections.virtual-card-cardyfie.index',compact(
            'page_title',
            'cardApi',
            'myCards',
            'transactions',
            'customer_card'
        ));

    }
    public function createPage(){
        $page_title = __("Create Virtual Card");
        $user       = userGuard()['user'];
        $supported_currency = support_currencies($this->card_supported_currencies['currencies']);
        $from_wallets = UserWallet::auth()->whereHas('currency',function($q) {
            $q->where("status",GlobalConst::ACTIVE);
        })->active()->get();

        $cardCharge     = $this->cardCharges;

        // if( $card_customer){
        //     $getCustomer = (new CardyFieHelper())->getCustomer( (string)$card_customer->ulid);
        //     if( $getCustomer['status'] == false){
        //         // $card_customer->delete();
        //         return $this->apiErrorHandle($getCustomer["message"]);
        //     }
        // }

        $card_mode     = strtoupper(cardyFieCardMode($this->api_mode));
        $card_customer = $user->cardyfieCardCustomer->where('env',$card_mode)->first();
        $totalCards    = CardyfieVirtualCard::where('user_id',$user->id)->where('env',$card_mode)->count() ?? 0;

        if($this->card_limit > 0 && $totalCards >= $this->card_limit){
            return redirect()->route("user.cardyfie.virtual.card.index")->with(['error' => [__("Sorry! You can not create more than")." ".$this->card_limit]]);
        }

        return view('user.sections.virtual-card-cardyfie.create',compact('page_title','user','cardCharge','supported_currency','from_wallets','card_customer'));
    }

    public function createCustomer(Request $request){

        $validated = Validator::make($request->all(),[
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
        ])->validate();

        $user       = userGuard()['user'];
        $card_mode = strtoupper(cardyFieCardMode($this->api_mode));
        $card_customer = $user->cardyfieCardCustomer->where('env', $card_mode)->first();

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
                    'status'         => "PENDING",
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

                //update few fields after store
                $storeData->ulid   = $createCustomer['data']['customer']['ulid'];
                $storeData->meta   = $createCustomer['data']['customer']['meta'];
                $storeData->status = $createCustomer['data']['customer']['status'];
                $storeData->save();
            }else{
                return back()->with(['error' => [__("Customer already exists")]]);
            }
            return redirect()->route("user.cardyfie.virtual.card.create")->with(['success' => [$createCustomer['message'][0] ?? __('Customer has been created successfully.')]]);

        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

    }

    public function editCustomer(){
        $user          = userGuard()['user'];
        $page_title    = __("Update Customer");
        $card_mode     = strtoupper(cardyFieCardMode($this->api_mode));
        $card_customer = $user->cardyfieCardCustomer->where('env', $card_mode)->first();
        if(!$card_customer){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        //check customer have on cardyfie platform or not
        $getCustomer = (new CardyFieHelper())->getCustomer( (string)$card_customer->ulid);


        if( $getCustomer['status'] == false){
            return $this->apiErrorHandle($getCustomer["message"]);
        }

        return view('user.sections.virtual-card-cardyfie.edit',compact('page_title','user','card_customer'));
    }

    public function updateCustomer(Request $request){
        $validated = Validator::make($request->all(),[
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
        ])->validate();

        $user          = userGuard()['user'];
        $card_mode     = strtoupper(cardyFieCardMode($this->api_mode));
        $card_customer = $user->cardyfieCardCustomer->where('env',$card_mode)->first();

        if(!$card_customer){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
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
                return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
            }


            return redirect()->back()->with(['success' => [__('Customer has been updated successfully.')]]);

        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

    }

    public function cardBuy(Request $request){

        $validated = Validator::make($request->all(),[
            'name_on_card'  => 'required|string|min:4|max:50',
            'card_tier'     => 'required|string|max:30',
            'card_type'     => 'required|string|max:30',
            'currency'      => "required|string",
            'from_currency' => "required|string|exists:currencies,code",
            'pin'               =>  $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ])->validate();

        $user          = userGuard()['user'];
        $basic_setting = BasicSettings::first();

        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
        }


        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();


        if(!$wallet) return back()->with(['error' => [__('User wallet not found')]]);

        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency) return back()->with(['error' => [__('Card Currency Not Found')]]);

        $cardCharge =  $this->cardCharges;
        $charges = $this->cardIssuesCharge($validated['card_tier'],$cardCharge,$wallet,$card_currency);

        if($charges['payable'] > $wallet->balance) return back()->with(['error' => [__("Your Wallet Balance Is Insufficient")]]);

        $card_mode = strtoupper(cardyFieCardMode($this->api_mode));
        $customer  = $user->cardyfieCardCustomer->where('env', $card_mode)->first();

        if(!$customer) return back()->with(['error' => [__("The customer doesn't create properly,Contact with owner")]]);
        $customer_card  = CardyfieVirtualCard::where('user_id',$user->id)->where('env',$card_mode)->count() ?? 0;

        if($this->card_limit  && $customer_card >= $this->card_limit) return back()->with(['error' => [__("Sorry! You can not create more than")." ".$this->card_limit ." ".__("card using the same email address.")]]);
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

            return redirect()->route("user.cardyfie.virtual.card.index")->with(['success' => [__('Virtual Card Buy Successfully')]]);
        }catch(Exception $e){

            DB::rollBack();

            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);

        }

    }

    public function cardDetails($ulid){
        $page_title = __("card Details");
        $myCard = CardyfieVirtualCard::where('ulid',$ulid)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);
        $cardApi = $this->api;
        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        $card_api = $get_card['data']['virtual_card'];
        return view('user.sections.virtual-card-cardyfie.details',compact(
            'page_title',
            'myCard',
            'cardApi',
            'card_api'
        ));

    }
    public function freezeUnfreeze(Request $request){
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|string',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->safe()->all();


        $card   = CardyfieVirtualCard::where('id',$request->data_target)->where('status',$validated['status'])->first();
        if($validated['status'] == GlobalConst::CARD_ENABLED_STATUS){
            $make_freeze  = (new CardyFieHelper())->freeze( (string)$card->ulid);
            if($make_freeze['status'] == false){
                return $this->apiErrorHandle($make_freeze["message"], true);
            }
            $card->status =  GlobalConst::CARD_FREEZE_STATUS;
            $card->save();

            $success = ['success' => [__('Card freeze successfully')]];
            return Response::success($success,null,200);

        }elseif($validated['status'] == GlobalConst::CARD_FREEZE_STATUS){
            $make_unfreeze  = (new CardyFieHelper())->unfreeze( (string)$card->ulid);
            if($make_unfreeze['status'] == false){
                return $this->apiErrorHandle($make_unfreeze["message"], true);
            }

            $card->status =  GlobalConst::CARD_ENABLED_STATUS;
            $card->save();

            $success = ['success' => [__('Card unfreeze successfully')]];
            return Response::success($success,null,200);
        }
    }

    public function makeDefaultOrRemove(Request $request) {

        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  CardyfieVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();
        $withOutTargetCards =  CardyfieVirtualCard::where('id','!=',$validated['target'])->where('user_id',$user->id)->get();

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

    public function closeCard(Request $request) {

        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  CardyfieVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();

        $make_close  = (new CardyFieHelper())->closeCard( (string)$targetCard->ulid);
        if($make_close['status'] == false){
            return $this->apiErrorHandle($make_close["message"]);
        }


        try{
            $targetCard->update([
                'status'         => GlobalConst::CARD_CLOSED_STATUS,
            ]);

        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return back()->with(['success' => [__('Card closed successfully!')]]);
    }


    public function cardDeposit($ulid){
        $page_title = __("Deposit to Virtual Card");
        $from_wallets = UserWallet::auth()->whereHas('currency',function($q) {
            $q->where("status",GlobalConst::ACTIVE);
        })->active()->get();

        $user       = userGuard()['user'];
        $cardReloadCharge = $this->cardCharges;
        $myCard = CardyfieVirtualCard::where('user_id', auth()->user()->id)->where('ulid',$ulid)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        $supported_currency = support_currencies([$myCard->currency] );

        return view('user.sections.virtual-card-cardyfie.fund',compact('page_title','user','cardReloadCharge','supported_currency','from_wallets','myCard'));
    }

    public function cardDepositConfirm(Request $request){
        $validated = Validator::make($request->all(),[
            'id'             => 'required|integer',
            'deposit_amount' => 'required|numeric|gt:0',
            'currency'       => "required|string",
            'from_currency'  => "required|string|exists:currencies,code",
            'pin'            => $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ])->validate();



        $basic_setting = BasicSettings::first();
        $user          = userGuard()['user'];
        $amount        = $validated['deposit_amount'];
        //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
        }

        $myCard =  CardyfieVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet) return back()->with(['error' => [__('User wallet not found')]]);

        $cardCharge = $this->cardCharges;
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency) return back()->with(['error' => [__('Card Currency Not Found')]]);


        $charges = $this->cardDepositCharges($amount,$cardCharge,$wallet,$card_currency);
        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];

        if($amount < $minLimit || $amount > $maxLimit)  return back()->with(['error' => [__("Please follow the transaction limit")]]);
        //daily and monthly
        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED,null);
        }catch(Exception $e){
           $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again."))]]);
        }

        if($charges['payable'] > $wallet->balance) return back()->with(['error' => [__("Your Wallet Balance Is Insufficient")]]);

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
                        'request_amount' => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_amount'    => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                        'card_pan'       => $myCard->masked_pan ?? "---- ----- ---- ----",
                        'trx'            => $trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e) {}
            }
            //admin notification
            $this->adminNotificationFund($trx_id,$charges,$amount,$user,$myCard);

            return redirect()->route("user.cardyfie.virtual.card.index")->with(['success' => [__('Card Funded Successfully')]]);
        }catch(Exception $e){
            return redirect()->back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

    }

    public function cardTransaction($ulid){
        $page_title = __("Virtual Card Transaction");
        $card = CardyfieVirtualCard::where('ulid',$ulid)->first();

        if(!$card) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $get_card_transactions  = (new CardyFieHelper())->getCardTransaction( (string) $card->ulid);

        if( $get_card_transactions['status'] == false){
            return $this->apiErrorHandle($get_card_transactions["message"]);
        }
        $get_card_transactions =  $get_card_transactions['data']['transactions'];


        return view('user.sections.virtual-card-cardyfie.trx',compact('page_title','card','get_card_transactions'));

    }

    public function cardWithdraw($card_id){
        $page_title = __("Virtual Card (Withdraw Amount)");
        $from_wallets = UserWallet::auth()->whereHas('currency',function($q) {
            $q->where("status",GlobalConst::ACTIVE);
        })->active()->get();

        $user       = userGuard()['user'];
        $cardCharges = $this->cardCharges;
        $myCard = CardyfieVirtualCard::where('user_id', auth()->user()->id)->where('ulid',$card_id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        $supported_currency = support_currencies([$myCard->currency ?? "USD"]);

        return view('user.sections.virtual-card-cardyfie.withdraw',compact('page_title','user','cardCharges','supported_currency','from_wallets','myCard'));
    }

    public function cardWithdrawConfirm(Request $request){
        $validated = Validator::make($request->all(),[
            'id'             => 'required|integer',
            'withdraw_amount' => 'required|numeric|gt:0',
            'currency'       => "required|string",
            'from_currency'  => "required|string|exists:currencies,code",
            'pin'            => $this->basic_settings->user_pin_verification == true ? 'required|digits:4' : 'nullable',
        ])->validate();

        $basic_setting = BasicSettings::first();
        $user          = userGuard()['user'];
        $amount        = $validated['withdraw_amount'];

         //check user pin
        if( $this->basic_settings->user_pin_verification == true){
            $pin_status = pin_verification($user,$validated['pin']);
            if( $pin_status['status'] == false) return back()->with(['error' => [$pin_status['message']]]);
        }

        $myCard =  CardyfieVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
        if( $get_card['status'] == false){
            return $this->apiErrorHandle($get_card["message"]);
        }

        //check real card balance
        if($amount > $get_card['data']['virtual_card']['card_balance']) return back()->with(['error' => [__("Your card doesn't have enough funds to complete this transaction")]]);

        $wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($validated) {
            $q->where("code",$validated['from_currency'])->active();
        })->active()->first();
        if(!$wallet) return back()->with(['error' => [__('User wallet not found')]]);

        $cardCharge = $this->cardCharges;
        $card_currency = Currency::active()->where('code',$validated['currency'])->first();
        if(!$card_currency) return back()->with(['error' => [__('Card Currency Not Found')]]);
        $charges = $this->cardWithdrawCharges($amount,$cardCharge,$wallet,$card_currency);

        $minLimit =  $cardCharge->min_limit *  $charges['card_currency_rate'];
        $maxLimit =  $cardCharge->max_limit *  $charges['card_currency_rate'];
        if($amount < $minLimit || $amount > $maxLimit)  return back()->with(['error' => [__("Please follow the transaction limit")]]);
        //daily and monthly

        try{
            (new TransactionLimit())->trxLimit('user_id',$wallet->user->id,PaymentGatewayConst::VIRTUALCARD,$wallet->currency,$amount,$cardCharge,PaymentGatewayConst::RECEIVED,null);
        }catch(Exception $e){
           $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again."))]]);
        }


        $withdraw_request  = (new CardyFieHelper())->withdrawCard( (string) $myCard->ulid, $amount);
        if( $withdraw_request['status'] == false){
            return $this->apiErrorHandle($withdraw_request["message"]);
        }

        $get_card  = (new CardyFieHelper())->getCard( (string)$myCard->ulid);
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
                    'trx_id'        => $trx_id,
                    'title'          => "Virtual Card (Withdraw Amount)",
                    'request_amount'=> get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'payable'       => get_amount($charges['payable'],$charges['from_currency'],$charges['precision_digit']),
                    'charges'       => get_amount( $charges['total_charge'],$charges['from_currency'],$charges['precision_digit']),
                    'card_amount'   => get_amount($amount,$charges['card_currency'],$charges['precision_digit']),
                    'card_pan'      => $myCard->masked_pan ?? "---- ----- ---- ----",
                    'status'        => "Success",
                ];
                try{
                    $user->notify(new Withdraw($user,(object)$notifyDataSender));
                }catch(Exception  $e){}
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

            return redirect()->route("user.cardyfie.virtual.card.index")->with(['success' => [__('Card Withdrawal Successful')]]);
        }catch(Exception $e){
            return redirect()->back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

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
                'title'         =>__('Buy Card'),
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

        $data['percent_charge']        = 0;
        $data['fixed_charge']          = ($charges->card_withdraw_fixed_fee * $exchange_rate);
        $data['total_charge']          = $data['percent_charge'] + $data['fixed_charge'];
        $data['from_wallet_balance']   = $wallet->balance;
        $data['payable']               = $data['from_amount'] - $data['total_charge'];
        $data['card_platform']         = "CardyFie";
        $data['precision_digit']       = $sPrecision;

        return $data;

    }
    //*****************************Card Buy Helpers******************************//



    //*****************************webhook check data******************************//

    public function getWebhookData(Request $request){

            try{
                $card_api = VirtualCardApi::first();
                $payload = $request->all();
                $secret = $card_api->config->cardyfie_webhook_secret ?? '';
                $received_signature = $request->header('X-Signature');

                $verify_signature = $this->verify_webhook_signature($payload, $received_signature, $secret);
                // $verify_signature = true;

                if (!$verify_signature) {
                    info('Invalid webhook signature', ['headers' => $request->headers->all()]);
                    return;
                }

                if($verify_signature == true){
                    $event = $payload['event']['type'];
                    $reference_id = $payload['data']['reference_id'];
                    if($event == 'customer.create' || $event == 'customer.status_update'){
                        $card_customer = CardyfieCardCustomer::where('reference_id',$reference_id)->first();
                        if($card_customer){
                            $customerApiPayload = $payload['data'];
                            $card_customer->update($customerApiPayload);
                        }

                    }elseif($event == 'card.issued' || $event == 'card.status_update'){

                       $card            = CardyfieVirtualCard::where('reference_id',$reference_id)->first();
                       $card->card_name = $payload['data']['card_name'];
                       $card->amount    = $payload['data']['card_balance'];
                       $card->currency  = $payload['data']['card_currency_code'];
                       $card->address   = $payload['data']['address'];
                       $card->status    = $payload['data']['status'];
                       $card->env       = $payload['data']['env'];
                       $card->save();

                    }else{
                        info('no event found');
                    }
                }
            }catch(Exception $e){
                info('error:'[$e->getMessage()]);
            }

    }
    //*****************************webhook check data******************************//

    //********************helpers functions***************************************//

    public function verify_webhook_signature($payload, $received_signature, $secret) {
        $expected_signature = hash_hmac("sha256", json_encode($payload), $secret);
        return hash_equals($expected_signature, $received_signature);
    }

    public function apiErrorHandle($apiErrors, $json = false){
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
        if($json == false){
            return back()->with(['error' => [$errorString ?? __("Something went wrong! Please try again.")]]);
        }else{
            $error = ['error' =>  [$errorString ??__("Something went wrong! Please try again.")]];
            return Response::error($error,null,400);
        }


    }
    //********************helpers functions***************************************//
}
