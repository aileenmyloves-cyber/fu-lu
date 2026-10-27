<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Trade;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Admin\Currency;
use App\Models\UserNotification;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MarketplaceTrxExport;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Notifications\User\Trade\AdminReject;
use App\Providers\Admin\BasicSettingsProvider;
use App\Notifications\User\Trade\MarketplaceMailSender;
use App\Notifications\User\Trade\MarketplaceMailReceiver;

class MarketplaceController extends Controller
{
    public function index()
    {
        $page_title = "All Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::MARKETPLACE)->where('attribute',PaymentGatewayConst::SEND)->orderBy('id', 'desc')->paginate(20);

        return view('admin.sections.marketplace.index', compact(
            'page_title',
            'transactions'
        ));
    }


      /**
     * Pending Add Money Logs View.
     * @return view $pending-add-money-logs
     */
    public function pending()
    {
        $page_title = "Pending Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::MARKETPLACE)->where('attribute',PaymentGatewayConst::SEND)->where('status', 2)->orderBy('id', 'desc')->paginate(20);
        return view('admin.sections.marketplace.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Complete Add Money Logs View.
     * @return view $complete-marketplace-logs
     */
    public function complete()
    {
        $page_title = "Complete Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::MARKETPLACE)->where('attribute',PaymentGatewayConst::SEND)->where('status', 1)->orderBy('id', 'desc')->paginate(20);
        return view('admin.sections.marketplace.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Canceled Add Money Logs View.
     * @return view $canceled-marketplace-logs
     */
    public function canceled()
    {
        $page_title = "Canceled Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::MARKETPLACE)->where('attribute',PaymentGatewayConst::SEND)->where('status', 4)->orderBy('id', 'desc')->paginate(20);
        return view('admin.sections.marketplace.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * This method for show details of add money
     * @return view $details-marketplace-logs
     */
    public function logDetails($id){
        $data = Transaction::with('trade')->where('id',$id)->with(
            'user:id,firstname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
        )->where('type', PaymentGatewayConst::MARKETPLACE)->first();

        $page_title = __("Marketplace details for").'  '.$data->trx_id;
        return view('admin.sections.marketplace.details', compact(
            'page_title',
            'data'
        ));
    }

    /**
     * This method for approved add money
     * @method PUT
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Request Response
     */
    public function approved(Request $request){

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = Transaction::with('trade')->where('id',$request->id)->where('status',2)->where('type', PaymentGatewayConst::MARKETPLACE)->first();
       
        if(!$data){
            return back()->with(['error' => [__('Marketplace request not found')]]);
        }
        $trade = Trade::find($data->trade->id);
        if(!$trade){
            return back()->with(['error' => [__('Trade not found')]]);
        }

        // seller profit
        $selling_wallet = UserWallet::where('currency_id', $trade->rate_currency_id)->where('user_id',$trade->user_id)->first();
        $selling_wallet->update([
            'balance' => $selling_wallet->balance + $data->request_amount,
        ]);

        // Buyer profit
        $Byer_wallet = UserWallet::where('currency_id', $trade->currency_id)->where('user_id',$data->user_id)->first();
        $Byer_wallet->update([
            'balance' => $Byer_wallet->balance + $trade->amount,
        ]);


        try{
            Transaction::where('trade_id', $trade->id)
                        ->where('status', 2)
                        ->where('type', PaymentGatewayConst::MARKETPLACE)
                        ->update(['status' => 1]);

            // Update trade
            $trade->update(['status' => 6]);

            $sender_currency = Currency::where('id',$trade->rate_currency_id)->first();
            if(!$sender_currency){
                return back()->with(['error' => [__('Sender currency not found')]]);
            }

            $receiver_currency = Currency::where('id',$trade->currency_id)->first();
            if(!$receiver_currency){
                return back()->with(['error' => [__('Receiver currency not found')]]);
            }

            $notification_data = [
                'sender_currency'   => $trade->rateCurrency->code,
                'receiver_currency' => $trade->saleCurrency->code,
                'sender_amount'     => $data->request_amount,
                'receiver_amount'   => $trade->amount,
                'total_charge'      => $data->charge->total_charge,
                'total_amount'      => $data->request_amount+$data->charge->total_charge,
                'exchange_rate'     => $data->request_amount / $trade->amount,
                'sPrecision'        => get_wallet_precision($sender_currency),
                'rPrecision'        => get_wallet_precision($receiver_currency),
            ];


            // Buyer Notification
            $sender = $data->user;
            $notification_content = [
                'title'         => __("Purchase request approved"),
                'message'       => "Your trade purchase request has been successfully approved by the admin. Purchase Amount "."". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($data->request_amount,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::MARKETPLACE_TRANSACTION_ADDED,
                'user_id'  =>  $sender->id,
                'message'   => $notification_content,
            ]);

            // Mail send
            $basic_settings = BasicSettingsProvider::get();
            try {
                if($basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'            => $data->trx_id,
                        'title'             => __("Your trade purchase request has been successfully approved by the admin"),
                        'request_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'payable'           => get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'charges'           => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'received_amount'   => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'status'            => __("Completed"),
                    ];
                    $sender->notify(new MarketplaceMailSender($sender,(object)$notifyDataSender));
                }
            } catch (Exception $th) {   }

            //push notification
            $basic_settings = BasicSettingsProvider::get();
            if($basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$sender->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {
                }
            }

            //sms notification
            $basic_setting = BasicSettings::first();
            try{
                //sender sms
                if($basic_setting->sms_notification == true){
                    sendSms($sender,'MARKETPLACE-ADMIN-SENDER',[
                        'request_amount'  => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'received_amount' => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'charge'          => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'from_user'      => auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'trx'             => $data->trx_id,
                        'time'            => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}


            // receiver record
            $notification_content = [
                'title'         => __("Purchase request approved"),
                'message'       => "Your trade purchase request has been successfully approved by the admin. Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($data->request_amount,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
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
                        'trx_id'            => $data->trx_id,
                        'title'             => __("Your trade purchase request has been successfully approved by the admin"),
                        'selling_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'     => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'status'            => __("Pending"),
                    ];
                    $user->notify(new MarketplaceMailReceiver($user,(object)$notifyDataSender));
                }
            } catch (Exception $e) {
            }

             //push notification
             if($basic_settings->push_notification == true){
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
                    sendSms($user,'MARKETPLACE-ADMIN-RECEIVER',[
                        'selling_amount' => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'  => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'from_user'      => auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'trx'            => $data->trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}


            return redirect()->back()->with(['success' => [__('Marketplace request approved successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }

    /**
     * This method for reject add money
     * @method PUT
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Request Response
     */
    public function rejected(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'reject_reason' => 'required|string',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::with('trade')->where('id',$request->id)->where('status',2)->where('type', PaymentGatewayConst::MARKETPLACE)->first();
        $trade = Trade::findOrFail($data->trade->id);
        try{
            Transaction::where('trade_id', $trade->id)
                        ->where('status', 2)
                        ->where('type', PaymentGatewayConst::MARKETPLACE)
                        ->update(['status' => 4,'reject_reason' => $request->reject_reason]);

            $trade->update(['status' => 1]);

            // // Buyer amount return
            $Byer_wallet = UserWallet::where('currency_id', $trade->rate_currency_id)->where('user_id',$data->user_id)->first();
            $buyer_amount = $trade->rate + $data->charge->total_charge;
            $Byer_wallet->update([
                'balance' => $Byer_wallet->balance + $buyer_amount,
            ]);



            $sender_currency = Currency::where('id',$trade->rate_currency_id)->first();
            if(!$sender_currency){
                return back()->with(['error' => [__('Sender currency not found')]]);
            }

            $receiver_currency = Currency::where('id',$trade->currency_id)->first();
            if(!$receiver_currency){
                return back()->with(['error' => [__('Receiver currency not found')]]);
            }

            $notification_data = [
                'sender_currency'   => $trade->rateCurrency->code,
                'receiver_currency' => $trade->saleCurrency->code,
                'sender_amount'     => $trade->rate,
                'receiver_amount'   => $trade->amount,
                'total_charge'      => $data->charge->total_charge,
                'total_amount'      => $trade->rate+$data->charge->total_charge,
                'exchange_rate'     => $trade->rate / $trade->amount,
                'sPrecision'        => get_wallet_precision($sender_currency),
                'rPrecision'        => get_wallet_precision($receiver_currency),
            ];


            // Buyer Notification
            $sender = $data->user;
            $notification_content = [
                'title'         => __("Your trade purchase request has been rejected by the admin"),
                'message'       => "Purchase Amount "."". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'      => NotificationConst::MARKETPLACE_TRANSACTION_ADDED,
                'user_id'  =>  $sender->id,
                'message'   => $notification_content,
            ]);

            // Mail send
            $basic_settings = BasicSettingsProvider::get();
            try {
                if($basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'            => $data->trx_id,
                        'title'             => __("Your trade purchase request has been rejected by the admin"),
                        'request_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'payable'           => get_amount($notification_data['total_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'charges'           => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'exchange_rate'     => get_amount(1,$notification_data['sender_currency']).' = '. get_amount($notification_data['exchange_rate'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'received_amount'   => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'reject_reason'     => $request->reject_reason,
                        'status'            => __("Completed"),
                    ];
                    $sender->notify(new AdminReject($sender,(object)$notifyDataSender));
                }
            } catch (Exception $th) {   }

            //push notification
            $basic_settings = BasicSettingsProvider::get();
            if($basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$sender->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {
                }
            }

            //sms notification
            $basic_setting = BasicSettings::first();
            try{
                //sender sms
                if($basic_setting->sms_notification == true){
                    sendSms($sender,'MARKETPLACE-ADMIN-REJECT-SENDER',[
                        'request_amount'  => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'received_amount' => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'charge'          => get_amount($notification_data['total_charge'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'from_user'      => auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'trx'             => $data->trx_id,
                        'time'            => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}


            // receiver record
            $notification_content = [
                'title'         => __("Your trade purchase request has been rejected by the admin"),
                'message'       => "Purchase Amount ". get_amount($trade->amount,$trade->saleCurrency->code,get_wallet_precision($trade->saleCurrency))." Rate Amount"." ". get_amount($trade->rate,$trade->rateCurrency->code,get_wallet_precision($trade->rateCurrency)),
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
                        'trx_id'            => $data->trx_id,
                        'title'             => __("Your trade purchase request has been rejected by the admin"),
                        'selling_amount'    => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'     => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'status'            => __("Pending"),
                    ];
                    $user->notify(new MarketplaceMailReceiver($user,(object)$notifyDataSender));
                }
            } catch (Exception $e) {
            }

             //push notification
             if($basic_settings->push_notification == true){
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
                    sendSms($user,'MARKETPLACE-ADMIN-REJECT-RECEIVER',[
                        'selling_amount' => get_amount($notification_data['sender_amount'],$notification_data['sender_currency'],$notification_data['sPrecision']),
                        'asking_amount'  => get_amount($notification_data['receiver_amount'],$notification_data['receiver_currency'],$notification_data['rPrecision']),
                        'from_user'      => auth()->user()->username.' ( '.auth()->user()->email ?? auth()->user()->full_mobile .' )',
                        'trx'            => $data->trx_id,
                        'time'           => now()->format('Y-m-d h:i:s A'),
                    ]);
                }
            }catch(Exception $e){}


            return redirect()->back()->with(['success' => [__('Trade request canceled successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }


    public function exportData(){
        $file_name = now()->format('Y-m-d_H:i:s') . "_marketplace_Logs".'.xlsx';
        return Excel::download(new MarketplaceTrxExport,$file_name);
    }




}
