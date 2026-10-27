<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\PaymentLinkController;




Route::controller(PaymentLinkController::class)->prefix('api/payment/link')->name('api.payment-link.')->group(function(){
    Route::get('share','shareData')->name('share');
    Route::post('submit','paymentLinkSubmit')->name('submit')->middleware('app.mode');
    //route for payment gateway
    Route::prefix('gateway/payment')->name('gateway.payment.')->group(function(){
        Route::get('success/stripe/{trx}', 'stripeSuccess')->name('stripe.success');
        Route::get('success/response/paypal/{gateway}','paypalSuccess')->name('paypal.success');
        Route::get("cancel/response/paypal/{gateway}",'paypalCancel')->name('paypal.cancel');
        Route::get('flutterwave/callback/response', 'flutterwaveCallback')->name('flutterwave.callback');

        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('btn.pay')->withoutMiddleware(['web']);
        //callback
        Route::post("callback/response/{gateway}",'callback')->name('callback')->withoutMiddleware(['web']);

        Route::get('success/response/{gateway}','successGlobal')->name('global.success');
        Route::get("cancel/response/{gateway}",'cancelGlobal')->name('global.cancel');

        // POST Route For Unauthenticated Request
        Route::post('success/response/{gateway}', 'postSuccess')->name('global.success')->withoutMiddleware(['web']);
        Route::post('cancel/response/{gateway}', 'postCancel')->name('global.cancel')->withoutMiddleware(['web']);

        //sslcommerz
        Route::post('sslcommerz/success','sllCommerzSuccess')->name('ssl.success');
        Route::post('sslcommerz/fail','sllCommerzFails')->name('ssl.fail');
        Route::post('sslcommerz/cancel','sllCommerzCancel')->name('ssl.cancel');
        //coingate
        Route::match(['get','post'],'coingate/success/response/{gateway}','coinGateSuccess')->name('coingate.success');
        Route::match(['get','post'],"coingate/cancel/response/{gateway}",'coinGateCancel')->name('coingate.cancel');
        //perfect Money
        Route::get('redirect/form/{gateway}', 'redirectUsingHTMLForm')->name('redirect.form')->withoutMiddleware(['auth']);
        //paystack


    });

});