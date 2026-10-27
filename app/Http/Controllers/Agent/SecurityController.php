<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecurityController extends Controller
{
    public function google2FA() {
        $page_title = __("Two Factor Authenticator");
        $qr_code = generate_google_2fa_auth_qr();
        return view('agent.sections.security.google-2fa',compact('page_title','qr_code'));
    }

    public function google2FAStatusUpdate(Request $request) {
        $validated = Validator::make($request->all(),[
            'target' => "required|numeric",
            'code'   => "required|string",
        ])->validate();

        $user = auth()->user();

        // Require a valid authenticator code before enabling/disabling 2FA (M2),
        // so the control cannot be toggled without proving possession of the secret.
        $google2FA = new \PragmaRX\Google2FA\Google2FA();
        if(!$user->two_factor_secret || !$google2FA->verifyKey($user->two_factor_secret, $validated['code'], 0)) {
            return back()->with(['error' => [__("The authenticator code is invalid.")]]);
        }

        try{
            $user->update([
                'two_factor_status'         => $user->two_factor_status ? 0 : 1,
                'two_factor_verified'       => true,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return back()->with(['success' => ['Security Setting Updated Successfully!']]);
    }
}
