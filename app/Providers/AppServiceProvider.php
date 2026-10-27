<?php

namespace App\Providers;

use App\Constants\ExtensionConst;
use App\Providers\Admin\ExtensionProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Mail\Transport\ReacheadTransport;
use Illuminate\Support\Facades\Mail;

ini_set('memory_limit','-1');
ini_set('serialize_precision','-1');
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);
        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }


        //laravel extend validation rules
        $this->extendValidationRule();

        // Mail::extend('reachead', function (array $config = []) {
        //     return new ReacheadTransport($config['key'] ?? null);
        // });

    }

    /**
     * extend laravel validation rules
     */
    public function extendValidationRule()
    {
        Validator::extend('g_recaptcha_verify', function($attribute, $value, $parameters, $validator) {

            // logger("WORKING-CAPTCH");

            $extension = ExtensionProvider::get()->where('slug', ExtensionConst::GOOGLE_RECAPTCHA_SLUG)->first();
            if(!$extension) return false;
            $secret_key = $extension->shortcode->secret_key->value ?? "";

            $response   =   Http::asForm()->post("https://www.google.com/recaptcha/api/siteverify",[
                'secret' => $secret_key,
                'response' => $value,
            ])->json();
            if(isset($response['success']) && $response['success'] == false) {
                logger('google recaptcha verification failed!', [$response]);
                return false;
            }
            return true;

        },":message");
    }
}