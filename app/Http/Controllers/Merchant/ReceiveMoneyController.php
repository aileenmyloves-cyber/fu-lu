<?php

namespace App\Http\Controllers\Merchant;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantNotification;
use App\Models\Merchants\MerchantWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\Merchant\ReceiveMoney\RefundBalance as ReceiveMoneyRefundBalance;
use App\Notifications\User\ReceiveMoney\RefundBalance;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReceiveMoneyController extends Controller
{
    public function index() {
        $page_title = __("Receive Money");
        $merchant = auth()->user();
        $merchant->createQr();
        $merchantQrCode = $merchant->qrCode()->first();
        $uniqueCode = $merchantQrCode->qr_code??'';
        $qrCode = generateQr($uniqueCode);
        return view('merchant.sections.receive-money.index',compact("page_title","uniqueCode","qrCode",'merchant'));
    }

    //***************************************For Make Payment Refund System****************************************/
    public function refundBalanceForMakePayment(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $basic_settings = BasicSettingsProvider::get();
        $transaction = Transaction::where('id', $validated['target'])->makePayment()->success()->first();
        if(!$transaction){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

        $details = $transaction->details;
        $balance_info = $details->charges;

        $merchant = Merchant::where('id',$transaction->merchant_id)->first();
        if(!$merchant){
            return back()->with(['error' => [__("Merchant not found")]]);
        }

        $merchant_wallet = MerchantWallet::where('id',$transaction->merchant_wallet_id)->first();
        if(!$merchant_wallet){
            return back()->with(['error' => [__("Merchant wallet not found!")]]);
        }


        $user  = User::where('username',$details->sender_username)->first();
        if(!$user){
            return back()->with(['error' => [__("Oops! User not exists")]]);
        }

        $user_wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($balance_info) {
            $q->where("code",$balance_info->sender_currency)->active();
        })->active()->first();
        if(!$user){
            return back()->with(['error' => [__("User wallet not found")]]);
        }

        $user_transaction = Transaction::where([
            ['trx_id', '=', $transaction->trx_id],
            ['user_id', '=', $user->id],
            ['user_wallet_id', '=', $user_wallet->id],
        ])->first();

        if(!$user_transaction){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $refundableBalance          = $balance_info->sender_amount;
        $merchantReducibleBalance   = $balance_info->receiver_amount;

        if($merchant_wallet->balance < $merchantReducibleBalance){
            return back()->with(['error' => [__("Sorry, insufficient balance")]]);
        }

        try{
            //balance refund to user nwallet
            $user_wallet->balance += $refundableBalance;
            $user_wallet->save();

            //reduce from merchant wallet
            $merchant_wallet->balance -=  $merchantReducibleBalance;
            $merchant_wallet->save();

            //Updated Transaction Status

            $transaction->update([
                'status' => PaymentGatewayConst::STATUSREFUND,
                'available_balance' =>   $merchant_wallet->balance
            ]);
            $user_transaction->update([
                'status' => PaymentGatewayConst::STATUSREFUND,
                'available_balance' => $user_wallet->balance
            ]);

            //send user notification
            $this->sendUserNotification($user,$user_transaction,$basic_settings);

            //send merchant notification
            $this->sendMerchantNotification($merchant,$transaction,$basic_settings);

            //admin notification
            $this->adminNotifications($transaction);
        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);

        }
        return redirect()->back()->with(['success' => [__('Refund processed successfully')]]);

    }

    //***************************************For Payment Gateway Refund System****************************************/
    public function refundBalanceForPaymentGateway(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $basic_settings = BasicSettingsProvider::get();
        $transaction = Transaction::where('id', $validated['target'])->merchantPayment()->success()->first();
        if(!$transaction){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

        $details = $transaction->details;
        $balance_info = $details->charges;


        $merchant = Merchant::where('id',$transaction->merchant_id)->first();
        if(!$merchant){
            return back()->with(['error' => [__("Merchant not found")]]);
        }


        $merchant_wallet = MerchantWallet::where('id',$transaction->merchant_wallet_id)->first();
        if(!$merchant_wallet){
            return back()->with(['error' => [__("Merchant wallet not found!")]]);
        }

        $user  = User::where('username',$details->sender_username)->first();
        if(!$user){
            return back()->with(['error' => [__("Oops! User not exists")]]);
        }

        $user_wallet = UserWallet::where('user_id',$user->id)->whereHas("currency",function($q) use ($balance_info) {
            $q->where("code",$balance_info->sender_currency)->active();
        })->active()->first();
        if(!$user){
            return back()->with(['error' => [__("User wallet not found")]]);
        }

        $user_transaction = Transaction::where([
            ['trx_id', '=', $transaction->trx_id],
            ['user_id', '=', $user->id],
            ['user_wallet_id', '=', $user_wallet->id],
        ])->first();

        if(!$user_transaction){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $refundableBalance          = $balance_info->sender_amount;
        $merchantReducibleBalance   = $balance_info->receiver_amount;

        if($merchant_wallet->balance < $merchantReducibleBalance){
            return back()->with(['error' => [__("Sorry, insufficient balance")]]);
        }


        try{
            //balance refund to user nwallet
            $user_wallet->balance += $refundableBalance;
            $user_wallet->save();

            //reduce from merchant wallet
            $merchant_wallet->balance -=  $merchantReducibleBalance;
            $merchant_wallet->save();


            //Updated Transaction Status

            $transaction->update([
                'status' => PaymentGatewayConst::STATUSREFUND,
                'available_balance' =>   $merchant_wallet->balance
            ]);
            $user_transaction->update([
                'status' => PaymentGatewayConst::STATUSREFUND,
                'available_balance' => $user_wallet->balance
            ]);

            //send user notification
            $this->sendUserNotification($user,$user_transaction,$basic_settings);

            //send merchant notification
            $this->sendMerchantNotification($merchant,$transaction,$basic_settings);

            //admin notification
            $this->adminNotifications($transaction);

        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);

        }
        return redirect()->back()->with(['success' => [__('Refund processed successfully')]]);

    }

    //***************************************Helper Functions*********************************************************/
    public function sendUserNotification($user,$user_transaction,$basic_settings){
        try{
            $details = $user_transaction->details;
            // email notification
            if( $basic_settings->email_notification == true){
                try{
                    $user->notify(new RefundBalance( $user,(object)$user_transaction));
                }catch(Exception $e){}
            }

            // Send sms
            if( $basic_settings->sms_notification == true){
                try{
                    sendSms($user,'REFUND',[
                        'title'        => "The refunded amount has been credited to your wallet"." ".".".__("web_trx_id")." ".$user_transaction->trx_id ,
                        'amount'      => get_amount($details->charges->sender_amount,$details->charges->sender_currency,get_wallet_precision($user_transaction->creator_wallet->currency)),
                        'balance'     => get_amount(($user_transaction->creator_wallet->balance + $details->charges->sender_amount),$details->charges->sender_currency,get_wallet_precision($user_transaction->creator_wallet->currency)),
                        'time'          => now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e){}
            }


            //Notification Contents
            $notification_content = [
                'title'         => "Refund Balance",
                'message'       => "From MERCHANT("." @".$details->receiver_username .") "." ".get_amount($details->charges->sender_amount,$details->charges->sender_currency,get_wallet_precision($user_transaction->creator_wallet->currency))." "."Successful."."TRX ID ".$user_transaction->trx_id.".",
                'image'         =>  get_image($user->image,'user-profile'),
            ];


            //store notification
            UserNotification::create([
                'type'      =>  NotificationConst::REFUND,
                'user_id'   =>  $user->id,
                'message'   =>  $notification_content,
            ]);

            //push notification
            if( $basic_settings->push_notification == true){
                try{
                        (new PushNotificationHelper())->prepare([$user->id],[
                            'title' => $notification_content['title'],
                            'desc'  => $notification_content['message'],
                            'user_type' => 'user',
                        ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e){
            throw new Exception(__("Something went wrong! Please try again."));
        }

    }
    public function sendMerchantNotification($merchant,$transaction,$basic_settings){

        try{
            $details = $transaction->details;
            //email notification
            if( $basic_settings->merchant_email_notification == true){
                try{
                    $merchant->notify(new ReceiveMoneyRefundBalance( $merchant,(object)$transaction));
                }catch(Exception $e){}
            }


            // Send sms
            if( $basic_settings->merchant_sms_notification == true){
                try{
                    sendSms($merchant,'REFUND',[
                        'title'        => "The refunded amount has been successfully sent to USER "."@".$details->sender_username." ".".".__("web_trx_id")." ".$transaction->trx_id ,
                        'amount'      => get_amount($details->charges->sender_amount,$details->charges->sender_currency,get_wallet_precision($transaction->creator_wallet->currency)),
                        'balance'     => get_amount($transaction->creator_wallet->balance-($details->charges->sender_amount),$details->charges->sender_currency,get_wallet_precision($transaction->creator_wallet->currency)),
                        'time'          => now()->format('Y-m-d h:i:s A')
                    ]);
                }catch(Exception $e){}
            }

            //Notification Contents
            $notification_content = [
                'title'         => "Refund Balance",
                'message' => "Successfully sent " . get_amount($details->charges->sender_amount, $details->charges->sender_currency, get_wallet_precision($transaction->creator_wallet->currency)) .
             " to USER (@" . $details->sender_username . "). TRX ID: " . $transaction->trx_id . ".",
                'image'         =>  get_image($merchant->image,'merchant-profile'),
            ];



            //store notification
            MerchantNotification::create([
                'type'          =>  NotificationConst::REFUND,
                'merchant_id'   =>  $merchant->id,
                'message'       =>  $notification_content,
            ]);

            //push notification
            if( $basic_settings->merchant_push_notification == true){
                try{
                        (new PushNotificationHelper())->prepare([$merchant->id],[
                            'title' => $notification_content['title'],
                            'desc'  => $notification_content['message'],
                            'user_type' => 'merchant',
                        ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e){
            throw new Exception(__("Something went wrong! Please try again."));
        }

    }
    public function adminNotifications($transaction){
        $details = $transaction->details;
        $trx_id  = $transaction->trx_id;
        $notification_content = [
            //email notification
            'subject' =>__("Refund Balance"),
            'greeting' =>__("Refund Balance Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("sender").": @".$details->receiver_username."<br>".__("Receiver").": @".$details->sender_username."<br>".__("Refund Balance")." : ".get_amount($details->charges->sender_amount, $details->charges->sender_currency, get_wallet_precision($transaction->creator_wallet->currency)),

            //push notification
            'push_title' => __("Refund Balance")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id.",".__("sender").": @".$details->receiver_username.",".__("Receiver").": @".$details->sender_username.",".__("Refund Balance")." : ".get_amount($details->charges->sender_amount, $details->charges->sender_currency, get_wallet_precision($transaction->creator_wallet->currency)),

            //admin db notification
            'notification_type' =>  NotificationConst::REFUND,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Refund Balance"." ".'Successful'." ".get_amount($details->charges->sender_amount, $details->charges->sender_currency, get_wallet_precision($transaction->creator_wallet->currency))." (".$trx_id.")",
            'admin_db_message' =>"Sender".": @".$details->receiver_username.","."Receiver".": @".$details->sender_username.","."Refund Balance"." : ".get_amount($details->charges->sender_amount, $details->charges->sender_currency, get_wallet_precision($transaction->creator_wallet->currency))
        ];


        try{
            //notification
            (new NotificationHelper())->admin(['admin.make.payment.index','admin.make.payment.export.data'])
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

}
