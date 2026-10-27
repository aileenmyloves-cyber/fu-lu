<?php

use App\Constants\GlobalConst;
use App\Http\Controllers\Api\AppSettingsController;
use App\Http\Controllers\Api\User\AddMoneyController;
use App\Http\Controllers\Api\User\AgentMoneyOutController;
use App\Http\Controllers\Api\User\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\User\Auth\LoginController;
use App\Http\Controllers\Api\User\AuthorizationController;
use App\Http\Controllers\Api\User\BillPayController;
use App\Http\Controllers\Api\User\CardyFieVirtualCardController;
use App\Http\Controllers\Api\User\ChatController;
use App\Http\Controllers\Api\User\GiftCardController;
use App\Http\Controllers\Api\User\MakePaymentController;
use App\Http\Controllers\Api\User\MarketplaceController;
use App\Http\Controllers\Api\User\MobileTopupController;
use App\Http\Controllers\Api\User\MoneyExchangeController;
use App\Http\Controllers\Api\User\MoneyOutController;
use App\Http\Controllers\Api\User\PaymentLinkController;
use App\Http\Controllers\Api\User\ReceiveMoneyController;
use App\Http\Controllers\Api\User\RecipientController;
use App\Http\Controllers\Api\User\ReferLevelController;
use App\Http\Controllers\Api\User\RemittanceController;
use App\Http\Controllers\Api\User\RequestMoneyController;
use App\Http\Controllers\Api\User\SecurityController;
use App\Http\Controllers\Api\User\SendMoneyController;
use App\Http\Controllers\Api\User\SetupPinController;
use App\Http\Controllers\Api\User\StripeVirtualController;
use App\Http\Controllers\Api\User\StrowalletVirtualCardController;
use App\Http\Controllers\Api\User\SudoVirtualCardController;
use App\Http\Controllers\Api\User\SupportTicketController;
use App\Http\Controllers\Api\User\TradeController;
use App\Http\Controllers\Api\User\TradeOfferController;
use App\Http\Controllers\Api\User\TransactionController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\VirtualCardController;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\SetupKyc;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    $message =  ['success'=>[__('Clear cache successfully')]];
    return Helpers::onlysuccess($message);
});
Route::get('get/basic/data', function() {
    $basic_settings = BasicSettingsProvider::get();
    $user_kyc = SetupKyc::userKyc()->first();
    $data =[
        'email_verification'        => $basic_settings->email_verification,
        'sms_verification'          => $basic_settings->sms_verification,
        'kyc_verification'          => $basic_settings->kyc_verification,
        'asset_base_url'            => get_asset_url('/'),
        'referral_system'           => refer_access(),
        'mobile_code'               => getDialCode(),
        'register_kyc_fields'       => $user_kyc,
        'countries'                 => freedom_countries(GlobalConst::USER)
    ];
    $message =  ['success'=>[__('Basic information fetch successfully')]];
    return Helpers::success($data,$message);
});
Route::controller(AppSettingsController::class)->prefix("app-settings")->group(function(){
    Route::get('/','appSettings');
    Route::get('languages','languages')->withoutMiddleware(['system.maintenance.api']);
});
Route::controller(AddMoneyController::class)->prefix("add-money")->group(function(){
    Route::get('success/response/paypal/{gateway}','success')->name('api.payment.success');
    Route::get("cancel/response/paypal/{gateway}",'cancel')->name('api.payment.cancel');
    Route::get('/flutterwave/callback', 'flutterwaveCallback')->name('api.flutterwave.callback');
    //Stripe
    Route::get('stripe/payment/success/{trx}','stripePaymentSuccess')->name('api.stripe.payment.success');
    //coingate
    Route::match(['get','post'],'coingate/success/response/{gateway}','coinGateSuccess')->name('api.coingate.payment.success');
    Route::match(['get','post'],"coingate/cancel/response/{gateway}",'coinGateCancel')->name('api.coingate.payment.cancel');
});

Route::prefix('user')->group(function(){
    //email verify before register
    Route::prefix('register')->middleware(['user.registration.permission'])->group(function(){
        Route::post('check/exist',[AuthorizationController::class,'checkExist']);
        Route::post('send/otp', [AuthorizationController::class,'sendOtp']);
        //for email
        Route::post('email/verify/otp',[AuthorizationController::class,"verifyEmailOtp"]);
        Route::post('email/resend/otp',[AuthorizationController::class,"resendEmailOtp"]);
        //for sms
        Route::post('sms/verify/otp',[AuthorizationController::class,"verifySmsOtp"]);
        Route::post('sms/resend/otp',[AuthorizationController::class,"resendSmsOtp"]);
    });

    Route::post('register',[LoginController::class,'register'])->middleware(['user.registration.permission']);
    Route::post('login',[LoginController::class,'login']);

    //forget password for email
    Route::prefix('forget')->group(function(){
       //send code
       Route::post('password', [ForgotPasswordController::class,'sendCode']);
       //email verification
       Route::post('verify/otp', [ForgotPasswordController::class,'verifyCode']);
       Route::post('email/resend', [ForgotPasswordController::class,'emailResend']);
       Route::post('reset/password', [ForgotPasswordController::class,'resetPassword']);
       //sms verification
       Route::post('sms/verify/otp', [ForgotPasswordController::class,'verifyCodeSms']);
       Route::post('sms/resend', [ForgotPasswordController::class,'smsResend']);
       Route::post('sms/reset/password', [ForgotPasswordController::class,'resetPasswordSms']);
    });
    //account re-verifications
    Route::middleware(['auth.api'])->group(function(){
       //email
       Route::post('send-code', [AuthorizationController::class,'sendMailCode']);
       Route::post('email-verify', [AuthorizationController::class,'mailVerify']);
       //phone
       Route::post('send/code/phone', [AuthorizationController::class,'sendPhoneCode']);
       Route::post('phone-verify', [AuthorizationController::class,'phoneVerify']);

       //2fa
       Route::post('google-2fa/otp/verify', [AuthorizationController::class,'verify2FACode']);
    });

    Route::middleware(['auth.api','verification.guard.api'])->group(function(){
        Route::post('logout', [LoginController::class,'logout']);
        Route::get('kyc', [AuthorizationController::class,'showKycFrom']);
        Route::post('kyc/submit', [AuthorizationController::class,'kycSubmit']);
        //pusher
        Route::get('pusher/beams-auth',[AuthorizationController::class,'pusherBeamsAuth'])->withoutMiddleware(['api','auth.api','verification.guard.api','CheckStatusApiUser','user.google.two.factor.api']);
        //pusher
        Route::middleware(['CheckStatusApiUser','user.google.two.factor.api'])->group(function () {
            Route::get('dashboard', [UserController::class,'home']);
            Route::post('verify/pin', [UserController::class,'pinVerify'])->middleware(['user.pin.setup.guard']);
            Route::get('get-remaining', [UserController::class,'getRemainingBalance']);
            Route::get('wallets', [UserController::class,'getWallets']);
            Route::get('profile', [UserController::class,'profile']);
            Route::post('profile/update', [UserController::class,'profileUpdate'])->middleware('app.mode.api');
            Route::post('password/update', [UserController::class,'passwordUpdate'])->middleware('app.mode.api');
            Route::post('delete/account', [UserController::class,'deleteAccount'])->middleware('app.mode.api');
            Route::get('notifications', [UserController::class,'notifications']);

            // setup pin
            Route::controller(SetupPinController::class)->prefix('setup/pin')->group(function(){
                Route::post('store', 'store');
                Route::post('update','update');
            });

             //virtual card flutterwave
            Route::middleware(['virtual_card_method:flutterwave','user.pin.setup.guard'])->group(function(){
                Route::controller(VirtualCardController::class)->prefix('my-card')->group(function(){
                    Route::get('/','index');
                    Route::get('charges','charges');
                    Route::post('create','cardBuy')->middleware('api.kyc');
                    Route::post('fund','cardFundConfirm')->middleware('api.kyc');
                    Route::get('details','cardDetails');
                    Route::get('transaction','cardTransaction');
                    Route::post('block','cardBlock');
                    Route::post('unblock','cardUnBlock');
                    Route::post('make-remove/default','makeDefaultOrRemove');
                });
            });
             //virtual card sudo
            Route::middleware(['virtual_card_method:sudo','user.pin.setup.guard'])->group(function(){
                Route::controller(SudoVirtualCardController::class)->prefix('my-card/sudo')->group(function(){
                    Route::get('/','index');
                    Route::get('charges','charges');
                    Route::get('details','cardDetails');
                    Route::post('create','cardBuy')->middleware('api.kyc');
                    Route::get('edit/customer','editCustomer');
                    Route::post('update/customer','updateCustomer');
                    Route::post('fund','cardFundConfirm')->middleware('api.kyc');
                    Route::get('details','cardDetails');
                    Route::get('transaction','cardTransaction');
                    Route::post('block','cardBlock');
                    Route::post('unblock','cardUnBlock');
                    Route::post('make-remove/default','makeDefaultOrRemove');
                });
            });
            //virtual card stripe
            Route::middleware(['virtual_card_method:stripe','user.pin.setup.guard'])->group(function(){
                Route::controller(StripeVirtualController::class)->prefix('my-card/stripe')->group(function(){
                    Route::get('/','index');
                    Route::get('details','cardDetails');
                    Route::post('create','cardBuy')->middleware('api.kyc');
                    Route::get('transaction','cardTransaction');
                    Route::post('inactive','cardInactive');
                    Route::post('active','cardActive');
                    Route::post('get/sensitive/data','getSensitiveData');
                    Route::post('make-remove/default','makeDefaultOrRemove');
                });
            });
            //strowallet virtual card
            Route::middleware(['virtual_card_method:strowallet','user.pin.setup.guard'])->group(function(){
                Route::controller(StrowalletVirtualCardController::class)->prefix('strowallet-card')->group(function(){
                    Route::get('/','index');
                    Route::get('charges','charges');

                    Route::post('create','cardBuy')->middleware('api.kyc');
                    Route::post('fund','cardFundConfirm')->middleware('api.kyc');
                    Route::post('withdraw','cardWithdrawConfirm')->middleware('api.kyc');

                    Route::get('details','cardDetails');
                    Route::get('transaction','cardTransaction');
                    Route::post('block','cardBlock');
                    Route::post('unblock','cardUnBlock')->name('block');
                    Route::post('make-remove/default','makeDefaultOrRemove');
                    Route::post('make-remove/default','makeDefaultOrRemove');
                    Route::get('webhook','webhookTransaction');

                    Route::get('get/card-holder','createCustomer');
                    Route::post('store/card-holder','storeCustomer');
                });
            });

            Route::middleware(['virtual_card_method:cardyfie','user.pin.setup.guard'])->group(function(){
                Route::controller(CardyFieVirtualCardController::class)->prefix('cardyfie-card')->group(function(){
                    Route::get('/','index');
                    Route::get('create/info','createPage');
                    Route::post('create/customer','createCustomer')->middleware(['api.kyc']);
                    Route::get('edit/customer/page','editCustomerPage');
                    Route::post('update/customer','updateCustomer')->middleware(['api.kyc']);
                    Route::post('create','cardBuy')->middleware(['api.kyc']);
                    Route::get('details','cardDetails');
                    Route::post('change/status','freezeUnfreeze');
                    Route::post('make-remove/default','makeDefaultOrRemove');
                    Route::post('deposit','cardDeposit')->middleware(['api.kyc']);
                    Route::post('withdraw','cardWithdraw')->middleware(['api.kyc']);
                    Route::get('transaction','cardTransaction');
                    Route::post('close','closeCard');

                });
            });

             //add money
            Route::controller(AddMoneyController::class)->prefix("add-money")->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/information','addMoneyInformation');
                Route::post('submit-data','submitData');
                //manual gateway
                Route::post('manual/payment/confirmed','manualPaymentConfirmedApi')->name('api.manual.payment.confirmed');

                Route::prefix('payment')->name('api.user.add.money.payment.')->group(function() {
                    Route::post('crypto/confirm/{trx_id}','cryptoPaymentConfirm')->name('crypto.confirm');
                });

                //redirect with Btn Pay
                Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('api.user.add.money.payment.btn.pay')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser','verification.guard.api','user.google.two.factor.api','user.pin.setup.guard']);

                // Global Gateway Response Routes
                Route::get('success/response/{gateway}','successGlobal')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser','verification.guard.api','user.google.two.factor.api','user.pin.setup.guard'])->name("api.user.add.money.payment.global.success");
                Route::get("cancel/response/{gateway}",'cancelGlobal')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser','verification.guard.api','user.google.two.factor.api','user.pin.setup.guard'])->name("api.user.add.money.payment.global.cancel");

                // POST Route For Unauthenticated Request
                Route::post('success/response/{gateway}', 'postSuccess')->name('api.user.add.money.payment.global.success')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser','verification.guard.api','user.google.two.factor.api','user.pin.setup.guard']);
                Route::post('cancel/response/{gateway}', 'postCancel')->name('api.user.add.money.payment.global.cancel')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser','verification.guard.api','user.google.two.factor.api','user.pin.setup.guard']);

                //Authorize
                Route::post('authorize-payment-submit','authorizePaymentSubmit')->name('api.user.add.money.authorize.payment.submit');

            });

             // P2P Trade
             Route::controller(TradeController::class)->prefix('trade')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/', 'index');
                Route::get('/currency/list', 'currencyList');
                Route::post('/submit','submit');
                Route::post('/confirm','confirm');
                Route::post('/close','cancelTrade');
                Route::post('/edit','edit');
                Route::post('/update','updateTrade');
            });

            // MY chat
            Route::controller(ChatController::class)->prefix("p2p/chat")->name("p2p.chat.")->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('user/check/', 'userCheck')->name('user.check');
                Route::post('create', 'create')->name('create');
                Route::post('conversation','conversation')->name('conversation');
                Route::post('message/send','messageSend')->name('message.send');
                // Route::post('/file-upload','chatFileUpload')->name('file.upload');
            });

            // Marketplace
            Route::controller(MarketplaceController::class)->prefix('marketplace')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/','index');
                Route::get('/transactions','transactions');
                Route::post('/buy','buy');
                Route::post('/confirm','confirm');
                Route::post('/evidence/submit','evidenceSubmit');
            });

            // Offer
            Route::controller(TradeOfferController::class)->prefix('offer')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/','index');
                Route::post('/submit','offerSubmit');
                Route::post('/counter/submit','counterSubmit');
                Route::post('/status','offerStatus');
                Route::post('/buy','buy');
                Route::post('/confirm','confirm');
                Route::post('/evidence/submit','evidenceSubmit');
            });

            //Receive Money
            Route::controller(ReceiveMoneyController::class)->prefix('receive-money')->group(function(){
                Route::get('/','index');
            });

            // My Status
            Route::controller(ReferLevelController::class)->prefix('my-status')->group(function() {
                Route::get('refer-data','referData');
            });

            //Money Exchange
            Route::controller(MoneyExchangeController::class)->prefix("money-exchange")->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/','index');
                Route::post('submit','moneyExchangeSubmit')->middleware('api.kyc');
            });

             //Send Money
            Route::controller(SendMoneyController::class)->prefix('send-money')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','sendMoneyInfo');
                Route::post('exist','checkUser');
                Route::post('qr/scan','qrScan');
                Route::post('confirmed','confirmedSendMoney')->middleware('api.kyc');
            });
             //Agent Money Out
            Route::controller(AgentMoneyOutController::class)->prefix('money-out')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','index');
                Route::post('confirmed','confirmed')->middleware('api.kyc');
                Route::post('check/agent','checkAgent');
                Route::post('qr/scan','qrScan');
            });

            //request Money
            Route::controller(RequestMoneyController::class)->prefix("request-money")->name('request.money.')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/','index')->name('index');
                Route::post('submit','submit')->name('submit')->middleware('api.kyc');
                Route::post('check/user','checkUser');
                Route::post('qr/scan','qrScan');
                Route::prefix("logs")->name("log.")->middleware("api.kyc")->group(function(){
                    Route::get('/','logLists')->name('list');
                    Route::post('approve','approved')->name('approve')->middleware('api.kyc');
                    Route::post('reject','rejected')->name('reject')->middleware('api.kyc');
                });
            });
            // Payment Link
            Route::controller(PaymentLinkController::class)->prefix('payment-links')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/', 'index');
                Route::post('/store', 'store');
                Route::get('/edit', 'edit');
                Route::post('/update', 'update');
                Route::post('/status', 'status');
            });

            //Withdraw Money
            Route::controller(MoneyOutController::class)->prefix('withdraw')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','moneyOutInfo');
                Route::post('insert','moneyOutInsert')->middleware('api.kyc');
                Route::post('manual/confirmed','moneyOutConfirmed')->name('api.withdraw.manual.confirmed')->middleware('api.kyc');
                Route::post('automatic/confirmed','confirmMoneyOutAutomatic')->name('api.withdraw.automatic.confirmed')->middleware('api.kyc');
               //get flutterWave banks
               Route::get('get/flutterwave/banks','getBanks');
               Route::get('get/flutterwave/bank/branches','getFlutterWaveBankBranches');
            });
            //Make Payment
             Route::controller(MakePaymentController::class)->prefix('make-payment')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','makePaymentInfo');
                Route::post('check/merchant','checkMerchant');
                Route::post('merchants/scan','qrScan');
                Route::post('confirmed','confirmedPayment')->middleware('api.kyc');
            });
             //Bill Pay
            Route::controller(BillPayController::class)->prefix('bill-pay')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','billPayInfo');
                Route::post('confirmed','billPayConfirmed')->middleware('api.kyc');
            });
             //mobile top up
            Route::controller(MobileTopupController::class)->prefix('mobile-topup')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','topUpInfo');
                Route::post('confirmed','topUpConfirmed')->middleware('api.kyc');
                //automatic method
                Route::prefix('automatic')->group(function(){
                    Route::get('check-operator','checkOperator');
                    Route::post('pay','payAutomatic')->middleware('api.kyc');
                });
            });
            //gift card
            Route::controller(GiftCardController::class)->prefix('gift-card')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('/', 'index');
                Route::get('all', 'allGiftCard');
                Route::get('search/', 'searchGiftCard');
                Route::get('details', 'giftCardDetails');
                Route::post('order', 'orderPlace')->middleware('api.kyc');
            });
            //Saved Recipient
            Route::controller(RecipientController::class)->prefix('recipient')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('list','recipientList');
                Route::get('save/info','saveRecipientInfo');
                Route::get('dynamic/fields','dynamicFields');
                Route::post('check/user','checkUser');
                Route::post('store','storeRecipient');
                Route::get('edit','editRecipient');
                Route::post('update','updateRecipient');
                Route::post('delete','deleteRecipient');
            });
             //Remittance
            Route::controller(RemittanceController::class)->prefix('remittance')->middleware(['user.pin.setup.guard'])->group(function(){
                Route::get('info','remittanceInfo');
                Route::post('confirmed','confirmed')->middleware('api.kyc');
                //for filters
                Route::post('get/recipient','getRecipient');
                // Route::post('get/recipient/transaction/type','getRecipientByTransType');
            });
             //transactions
            Route::controller(TransactionController::class)->prefix("transactions")->group(function(){
                Route::get('/{slug?}','index');
            });
            //google-2fa
            Route::controller(SecurityController::class)->prefix("security")->group(function(){
                Route::get('google-2fa', 'google2FA');
                Route::post('google-2fa/status/update', 'google2FAStatusUpdate')->middleware('app.mode.api');
            });

            Route::controller(SupportTicketController::class)->prefix("support-ticket")->name("api.support.ticket")->group(function(){
                Route::get('index', 'getTickets')->name('index');
                Route::get('conversation', 'conversation')->name('conversation');
                Route::post('store', 'storeTicket')->name('store');
                Route::post('send/message', 'sendMessage')->name('send.message');
            });

        });

    });

});