<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Global\GlobalController;

Route::name('api.global')->prefix('global')->group(function(){
    Route::post('contact/message', [GlobalController::class, "sendContactMessage"]);
    Route::post('subscribe/request', [GlobalController::class, "sendSubscribeRequest"]);
    Route::get('site-section-data', [GlobalController::class, "getSiteSectionData"]);
});