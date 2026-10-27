<?php

namespace App\Providers;

use App\Models\Admin\BasicSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {

            try {
                if (Schema::hasTable('basic_settings')) {
                    $basic_settings = BasicSettings::first();
                }
            } catch (\Throwable $e) {
                $basic_settings = null;
            }


            Route::middleware(['system.maintenance.api','api'])
                ->prefix('api')
                ->group(base_path('routes/api/api.php'));

            Route::middleware(['system.maintenance.api','api'])
            ->prefix('merchant-api')
                ->group(base_path('routes/api/merchant_api.php'));

            Route::middleware(['system.maintenance.api','api'])
                ->prefix('agent-api')
                ->group(base_path('routes/api/agent_api.php'));

            Route::middleware(['api'])
                ->group(base_path('routes/api/payment-link/public.php'));

            Route::middleware(['system.maintenance.api','api'])
                ->prefix('api')
                ->group(base_path('routes/api/global.php'));

            Route::middleware(['web','system.maintenance'])
                ->group(base_path('routes/web.php'));

            Route::middleware(['web','auth','verification.guard','sms.verification.guard','user.google.two.factor','system.maintenance'])

                ->group(base_path('routes/user.php'));


            Route::prefix($basic_settings->admin_prefix ?? 'admin')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web','auth:merchant','verification.guard.merchant','merchant.sms.verification.guard','merchant.google.two.factor','system.maintenance'])
            ->group(base_path('routes/merchant.php'));

            Route::middleware(['web','auth:agent','verification.guard.agent','agent.sms.verification.guard','agent.google.two.factor','system.maintenance'])
            ->group(base_path('routes/agent.php'));

            Route::middleware(['web','system.maintenance'])
                ->group(base_path('routes/auth.php'));

            Route::middleware(['web'])
                ->group(base_path('routes/global.php'));

            Route::middleware(['api'])
                ->group(base_path('routes/payment-gateway/qr_pay/v1/routes.php'));

            //demo checkout
            Route::middleware(['web','system.maintenance'])
            ->group(base_path('routes/payment-gateway/qr_pay/v1/checkout.php'));

            $this->mapInstallerRoute();
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure/Place installer routes.
     *
     * @return void
     */
    protected function mapInstallerRoute() {
        if(file_exists(base_path('resources/installer/src/routes/web.php'))) {
            Route::middleware('web')
                ->group(base_path('resources/installer/src/routes/web.php'));
        }
    }
}
