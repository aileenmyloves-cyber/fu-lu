<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class SecurityController extends Controller
{
    public function google2FA(){

        $user = authGuardApi()['user'];
        $qr_text    = generate_google_2fa_user_qr_text($user);
        $qr_code    = QrCode::format('svg')->size(200)->generate($qr_text)->toHtml();

        $qr_secrete = $user->two_factor_secret;
        $qr_status = $user->two_factor_status;

        $data = [
            'qr_code'    => $qr_code,
            'qr_secrete' => $qr_secrete,
            'qr_status'  => $qr_status,
            'alert' => __("Don't forget to add this application in your google authentication app. Otherwise you can't login in your account.",)
        ];

        $message = ['success'=>[ __('Data Fetch Successful')]];
        return Helpers::success( $data,$message);
    }


    public function google2FAStatusUpdate(Request $request){

        $validator = Validator::make($request->all(),[
            'code'   => "required|string|max:6",
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();
        $user = authGuardApi()['user'];

        // Require a valid authenticator code before enabling/disabling 2FA (M2),
        // so the control cannot be toggled without proving possession of the secret.
        $google2FA = new \PragmaRX\Google2FA\Google2FA();
        if(!$user->two_factor_secret || !$google2FA->verifyKey($user->two_factor_secret, $validated['code'], 0)) {
            $error = ['error'=>[__("The authenticator code is invalid.")]];
            return Helpers::error($error);
        }
        
        try{
            $user->update([
                'two_factor_status'         => $user->two_factor_status ? 0 : 1,
                'two_factor_verified'       => true,
            ]);
        }catch(Exception $e) {
           $error = ['error'=>[__('Something went wrong! Please try again.')]];
           return Helpers::error($error);
        }
        $message = ['success'=>[__('Google 2FA Updated Successfully')]];
        return Helpers::onlysuccess($message);
    }
}