<?php

namespace App\Providers;

use Exception;
use App\Models\User;
use App\Constants\GlobalConst;
use App\Models\Admin\Currency;
use App\Models\Admin\Language;
use App\Models\Admin\SetupSeo;
use App\Models\VirtualCardApi;
use App\Models\Admin\Extension;
use App\Constants\LanguageConst;
use App\Models\Admin\GatewayAPi;
use App\Models\UserSupportTicket;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\ModuleSetting;
use App\Models\Admin\ReferralSetting;
use Illuminate\Support\Facades\Config;
use App\Models\Admin\SystemMaintenance;
use Illuminate\Support\ServiceProvider;
use App\Providers\Admin\CurrencyProvider;
use App\Providers\Admin\ExtensionProvider;
use App\Models\Admin\FrontendHeaderSection;
use App\Providers\Admin\BasicSettingsProvider;

class CustomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->startingPoint();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try{

            $default_language = Language::where('status',GlobalConst::ACTIVE)->first();
            $default_language_code = $default_language->code ?? LanguageConst::NOT_REMOVABLE;
            $view_share = [];
            $view_share['basic_settings']               = BasicSettings::first();
            $view_share['default_currency']             = Currency::default();
            $view_share['__languages']                  = Language::get();
            $view_share['all_user_count']               = User::count();
            $view_share['email_verified_user_count']    = User::where('email_verified', 1)->count();
            $view_share['kyc_verified_user_count']      = User::where('kyc_verified', 1)->count();
            $view_share['default_currency']             = Currency::default();
            $view_share['__extensions']                 = Extension::get();
            $view_share['pending_ticket_count']         = UserSupportTicket::pending()->get()->count();
            $view_share['module']                       = ModuleSetting::get();
            $view_share['card_limit']                   = VirtualCardApi::first()->card_limit;
            $view_share['card_api']                     = VirtualCardApi::first();
            $view_share['default_language_code']        = $default_language_code;
            $view_share['not_removable_code']           = LanguageConst::NOT_REMOVABLE;
            $view_share['seo_data']                     = SetupSeo::first();
            $view_share['payLink']                      = GatewayAPi::first();
            $view_share['system_maintenance']           = SystemMaintenance::first();
            $view_share['referral_info']                = ReferralSetting::first();
            $view_share['personal']                     = FrontendHeaderSection::personal()->get();
            $view_share['business']                     = FrontendHeaderSection::business()->get();
            $view_share['enter_price']                  = FrontendHeaderSection::enterPrice()->get();
            $view_share['company']                      = FrontendHeaderSection::company()->get();

            view()->share($view_share);

            if(!empty($view_share['basic_settings']?->mail_config?->method) && $view_share['basic_settings']->mail_config->method == 'reachead') {
                $key =  $view_share['basic_settings']?->mail_config?->api_key;
                Config::set('mail.mailers.reachead.key', $key);
            }

            if(!empty($view_share['basic_settings']?->frontend_url)) {
                $frontendUrls = array_values((array) $view_share['basic_settings']->frontend_url);
                Config::set('cors.allowed_origins', $frontendUrls);
            }


            $this->app->bind(BasicSettingsProvider::class, function () use ($view_share) {
                return new BasicSettingsProvider($view_share['basic_settings']);
            });
            $this->app->bind(CurrencyProvider::class, function () use ($view_share) {
                return new CurrencyProvider($view_share['default_currency']);
            });
            $this->app->bind(ExtensionProvider::class, function () use ($view_share) {
                return new ExtensionProvider($view_share['__extensions']);
            });

        }catch(Exception $e) {
            //
        }
    }

    public function startingPoint() {
        if(config('app.purchase_code') == null) {
            Config::set('starting-point.status',true);
            Config::set('starting-point.point','/project/install/welcome');
        }
    }
}
