<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\BasicSettings;

class PinSetupGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $basic_settings     = BasicSettings::first();
        if( $basic_settings->user_pin_verification == true){
            $user           = authGuardApi()['user'];
            if($user->pin_status == false){
                if(authGuardApi()['guard']  == "api") {
                    $message = ['error'=>[__("Kindly complete your PIN setup before proceeding.")]];
                    $data =[
                        'pin_status' => $user->pin_status
                    ];
                    return Helpers::error($message,$data);
                }else{
                    return redirect()->route('user.setup.pin.index')->with(['warning' => ['Kindly complete your PIN setup before proceeding.']]);
                }
            }
        }

        return $next($request);
    }
}
