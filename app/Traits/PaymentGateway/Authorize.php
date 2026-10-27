<?php
namespace App\Traits\PaymentGateway;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Models\TemporaryData;
use App\Models\UserNotification;
use App\Traits\TransactionAgent;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Traits\PayLink\TransactionTrait;
use App\Http\Helpers\PushNotificationHelper;

trait Authorize{
    use TransactionAgent,TransactionTrait;

    public function authorizeInit($output = null){
        if(!$output) $output = $this->output;

        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
           return $this->setupAuthorizeInit($output);
         }else{

             return  $this->setupAuthorizeInitPayLink($output);
         }
    }
    public function authorizeInitApi($output = null){
        if(!$output) $output = $this->output;
        return $this->setupAuthorizeInit($output);

    }

    public function setupAuthorizeInit($output){
        $temp_record_token = generate_unique_string('temporary_datas','identifier',60);
        $junk_data = $this->authorizeJunkInsert($temp_record_token);
        if(request()->expectsJson()) {

            $this->output['redirection_response'] = [];
            $this->output['redirect_links']       = [];
            $this->output['gateway_alias']        = "authorize";
            $this->output['temp_identifier']      = $temp_record_token ?? '';
            if(authGuardApi()['guard'] == 'api'){
                $this->output['redirect_url']         =  route('api.user.add.money.authorize.payment.submit');
            }else{
                 $this->output['redirect_url']         =  route('api.agent.add.money.authorize.payment.submit');
            }

            return $this->get();
        }

        if(userGuard()['guard'] == 'web'){
           return redirect()->route('user.add.money.authorize.card.info',$junk_data->identifier);
        }elseif(userGuard()['guard'] == 'agent'){
            return redirect()->route('agent.add.money.authorize.card.info',$junk_data->identifier);
        }

    }
    public function setupAuthorizeInitPayLink($output){
        $temp_record_token = generate_unique_string('temporary_datas','identifier',60);
        $junk_data = $this->authorizeJunkInsertPayLink($temp_record_token);
        return redirect()->route('payment-link.gateway.payment.authorize.card.info',$junk_data->identifier);
    }

    public function authorizeJunkInsert($temp_identifier) {
        $output = $this->output;
        $creator_table = $creator_id = $wallet_table = $wallet_id = null;

        if(authGuardApi()['type']  == "AGENT"){
            $creator_table = authGuardApi()['user']->getTable();
            $creator_id    = authGuardApi()['user']->id;
            $creator_guard = authGuardApi()['guard'];
            $wallet_table  = $output['wallet']->getTable();
            $wallet_id     = $output['wallet']->id;
        }else{
            $creator_table = auth()->guard(get_auth_guard())->user()->getTable();
            $creator_id    = auth()->guard(get_auth_guard())->user()->id;
            $creator_guard = get_auth_guard();
            $wallet_table  = $output['wallet']->getTable();
            $wallet_id     = $output['wallet']->id;
        }

        $data = [
            'gateway'       => $output['gateway']->id,
            'currency'      => $output['currency']->id,
            'validate_data' => $output['request_data'],
            'amount'        => json_decode(json_encode($output['amount']),true),
            'response'      => [],
            'wallet_table'  => $wallet_table,
            'wallet_id'     => $wallet_id,
            'creator_table' => $creator_table,
            'creator_id'    => $creator_id,
            'creator_guard' => $creator_guard,
        ];


        return TemporaryData::create([
            'type'          => PaymentGatewayConst::AUTHORIZE,
            'identifier'    => $temp_identifier,
            'data'          => $data,
        ]);

    }

     public function authorizeJunkInsertPayLink($temp_identifier) {
        $output = $this->output;
        $wallet_table = $output['wallet']->getTable();
        $wallet_id = $output['wallet']->id;
        $user_relation_name = strtolower($output['user_type'])??'user';

        $data = [
            'type'                  => $output['type'],
            'gateway'               => $output['gateway']->id,
            'currency'              => $output['currency']->id,
            'validated'             => $output['validated'],
            'charge_calculation'    => json_decode(json_encode($output['charge_calculation']),true),
            'response'              => [],
            'wallet_table'          => $wallet_table,
            'wallet_id'             => $wallet_id,
            'creator_guard'         => $output['user_guard']??'',
            'user_type'             => $output['user_type']??'',
            'user_id'               => $output['wallet']->$user_relation_name->id??'',
        ];
        return TemporaryData::create([
            'type'       => PaymentGatewayConst::AUTHORIZE,
            'identifier' => $temp_identifier,
            'data'       => $data,
        ]);
    }

    // For get the gateway credentials
    function authorizeCredentials($temp_data){
        $gateway             = PaymentGateway::where('id',$temp_data->data->gateway)->first() ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $credentials         = $gateway->credentials;
        $app_login_id        = getPaymentCredentials($credentials,'App Login ID');
        $transaction_key     = getPaymentCredentials($credentials,'Transaction Key');
        $signature_key       = getPaymentCredentials($credentials,'Signature Key');

        $mode           = $gateway->env;

        $authorize_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if(array_key_exists($mode,$authorize_register_mode)) {
            $mode = $authorize_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }

        return (object) [
            'app_login_id'          => $app_login_id,
            'transaction_key'       => $transaction_key,
            'signature_key'         => $signature_key,
            'mode'                  => $mode,
            'code'                  => $gateway->code
        ];
    }

    public function isAuthorize($gateway)
    {
        $search_keyword = ['authorize','authorize gateway','gateway authorize','authorize payment gateway'];
        $gateway_name = $gateway->name;

        $search_text = Str::lower($gateway_name);
        $search_text = preg_replace("/[^A-Za-z0-9]/","",$search_text);
        foreach($search_keyword as $keyword) {
            $keyword = Str::lower($keyword);
            $keyword = preg_replace("/[^A-Za-z0-9]/","",$keyword);
            if($keyword == $search_text) {
                return true;
                break;
            }
        }
        return false;
    }


    public function authorizeSuccess($output) {


        if(!$output) $output = $this->output;
        $output['capture']      = $output['tempData']['data']->response ?? "";

        // need to insert new transaction in database
        try{
            $user_guard = request()->expectsJson() ? authGuardApi()['type'] : userGuard()['type'];


            if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
                if($user_guard == "USER"){
                    return $this->createTransactionAuthorize($output);
                }else{
                    return $this->createTransactionChildRecords($output,PaymentGatewayConst::STATUSSUCCESS);
                }
            }else{
                return $this->createTransactionPayLink($output,PaymentGatewayConst::STATUSSUCCESS);
            }
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
     public function createTransactionAuthorize($output) {
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordAuthorize($output,$trx_id);
        $this->insertChargesAuthorize($output,$inserted_id);
        $this->adminNotification($trx_id,$output,PaymentGatewayConst::STATUSSUCCESS);
        $this->insertDeviceAuthorize($output,$inserted_id);
        // $this->removeTempDataAuthorize($output);

         //send sms & email notifications
         $this->sendNotifications($output,$trx_id);

        if($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }

    }
    public function insertRecordAuthorize($output,$trx_id) {

        $trx_id = $trx_id;
        DB::beginTransaction();
        try{

            if($this->predefined_user) {
                $user = $this->predefined_user;
            }elseif(Auth::guard(get_auth_guard())->check()){
                $user = auth()->guard(get_auth_guard())->user();
            }
            $id = DB::table("transactions")->insertGetId([
               'user_id'                     => $user->id,
               'user_wallet_id'              => $output['wallet']->id,
               'payment_gateway_currency_id' => $output['currency']->id,
               'type'                        => PaymentGatewayConst::TYPEADDMONEY,
               'trx_id'                      => $trx_id,
               'request_amount'              => $output['amount']->requested_amount,
               'payable'                     => $output['amount']->total_amount,
               'available_balance'           => $output['wallet']->balance + $output['amount']->requested_amount,
               'remark'                      => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEADDMONEY," ")) . " by " . $output['gateway']->name,
               'details'                     => json_encode([ 'amount'    => $output['amount'] ]),
               'status'                      => true,
               'attribute'                   => PaymentGatewayConst::RECEIVED,
               'created_at'                  => now(),
            ]);

            $this->updateWalletBalanceAuthorize($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function updateWalletBalanceAuthorize($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }
    public function insertChargesAuthorize($output,$id) {

        if($this->predefined_user) {
            $user = $this->predefined_user;
        }elseif(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }

        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $output['amount']->percent_charge,
                'fixed_charge'      => $output['amount']->fixed_charge,
                'total_charge'      => $output['amount']->total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => __("Add Money"),
                'message'       => __("Your Wallet")." (".$output['wallet']->currency->code.")  ".__("balance  has been added")." ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => get_image($user->image,'user-profile'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);

             //Push Notifications
             try{
                (new PushNotificationHelper())->prepare([$user->id],[
                    'title' => $notification_content['title'],
                    'desc'  => $notification_content['message'],
                    'user_type' => 'user',
                ])->send();
             }catch(Exception $e) {}

            //admin notification

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    public function insertDeviceAuthorize($output,$id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        DB::beginTransaction();
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
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    public function removeTempDataAuthorize($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }


}

?>