<?php

namespace App\Traits\PaymentGateway;

use Exception;
use App\Models\UserWallet;
use Jenssegers\Agent\Agent;
use App\Models\TemporaryData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;

trait ManualCrowPayment{


    /**
     * This method for insert forexcrow
     * @return $id;
     */
    public function forexCrowInsert($data){
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


    /**
     * This method for insert record in transaction table
     * @return $id;
     */
    public function insertRecordSender($data,$trade_id = null, $trx_id, $get_values = null) {

        try{

            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => Auth::user()->id,
                'trade_id'                    => $trade_id,
                'payment_gateway_currency_id' => $data->payment_gateway_currency_id ?? NULL,
                'user_wallet_id'              => $data->wallet->id,
                'type'                        => $data->transaction_type,
                'trx_id'                      => $trx_id ?? '',
                'request_amount'              => $data->amount ?? "",
                'payable'                     => $data->subtotal ?? "",
                'available_balance'           => $data->wallet->balance - $data->total_amount,
                'remark'                      => ucwords(remove_speacial_char($data->transaction_type)),
                'details'                     => json_encode([
                                                 'get_values' => $get_values,
                                                 'saller' => $data->saller_email,
                                                 'buyer' => auth()->user()->email,
                                                 'sale_amount' => $data->will_get,
                                                 'sale_currency' => $data->sale_currency,
                                                 'rate_amount'  => $data->amount,
                                                 'rate_currency' => $data->rate_currency,
                                                 'fixed_charge'    => $data->fixed_charge,
                                                 'percent_charge'    => $data->percent_charge,
                                                 'total_charge'    => $data->total_charge,
                                                 'total_amount'    => $data->total_amount,
                                                ]),
                'status'                      => PaymentGatewayConst::STATUSPENDING,
                'created_at'                  => now(),
            ]);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    /**
     * This method for insert record in transaction charge table
     * @return $id;
     */
    public function insertChargesSender($data,$id) {
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

    /**
     * This method for insert record in transaction device table
     * @return $id;
    */


    public function insertDeviceSender($id) {
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


    public function removeTempDataManual($identifier) {
        if(isset($identifier)){
             TemporaryData::where("identifier",$identifier)->delete();
        }
    }

    public function userWalletUpdate($amount, $wallet){

        $wallet = UserWallet::find($wallet->id);

        $wallet->update([
            'balance' => $wallet->balance - $amount,
        ]);
    }





    public function insertRecordReceiver($data,$trade_id = null, $trx_id, $get_values = null) {

        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => $data->trade_creator_id,
                'trade_id'                    => $trade_id,
                'payment_gateway_currency_id' => $data->payment_gateway_currency_id ?? NULL,
                'user_wallet_id'              => $data->wallet->id,
                'type'                        => $data->transaction_type,
                'trx_id'                      => $trx_id,
                'request_amount'              => $data->amount,
                'payable'                     => $data->subtotal,
                'available_balance'           => $data->wallet->balance - $data->total_amount,
                'remark'                      => ucwords(remove_speacial_char($data->transaction_type)),
                'details'                     => json_encode([
                                                    'get_values' => $get_values,
                                                    'saller' => $data->saller_email,
                                                    'buyer' => auth()->user()->email,
                                                    'sale_amount' => $data->will_get,
                                                    'sale_currency' => $data->sale_currency,
                                                    'rate_amount'  => $data->amount,
                                                    'rate_currency' => $data->rate_currency,
                                                    'fixed_charge'    => $data->fixed_charge,
                                                    'percent_charge'    => $data->percent_charge,
                                                    'total_charge'    => $data->total_charge,
                                                    'total_amount'    => $data->total_amount,
                                                ]),
                'status'                      => PaymentGatewayConst::STATUSPENDING,
                'attribute'                   => PaymentGatewayConst::RECEIVED,
                'created_at'                  => now(),
            ]);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $id;
    }


    public function insertChargesReceiver($data,$id) {
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


    public function insertDeviceReceiver($id) {
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
}
