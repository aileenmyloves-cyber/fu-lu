<?php

namespace App\Http\Controllers\Api\Merchant;

use Exception;
use Illuminate\Http\Request;
use App\Http\Helpers\Api\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SetupPinController extends Controller
{
    /**
     * Method for store pin information
     * @param Illuminate\Http\Request $request
     */

    public function store(Request $request){
        $validator      = Validator::make($request->all(),[
            'pin_code'  => 'required|digits:4',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated  = $validator->validated();
        $user       = authGuardApi()['user'];
        if($user->pin_status == true && $user->pin_code != null){
            $error = ['error'=>[__("You have already set a Security PIN. To update it, please use the update form.")]];
            return Helpers::error($error);
        }
        try{
            $user->update([
                'pin_code'  => $validated['pin_code'],
                'pin_status'    => true,
            ]);
        }catch(Exception $e){
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);

        }
        $message =  ['success'=>[__('Your PIN has been set up successfully.')]];
        return Helpers::onlysuccess($message);
    }
    /**
     * Method for update pin information
     * @param Illuminate\Http\Request $request
     */
    public function update(Request $request){
        $validator      = Validator::make($request->all(),[
            'old_pin'  => 'required|digits:4',
            'new_pin'  => 'required|digits:4',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated  = $validator->validated();
        $user       = authGuardApi()['user'];
        if($validated['old_pin'] != $user->pin_code){
            $error = ['error'=>[__('Old pin code not matched!')]];
            return Helpers::error($error);
        }
        //old pin check
        if($validated['new_pin'] == $user->pin_code){
            $error = ['error'=>[__('New PIN must be different from the old PIN.')]];
            return Helpers::error($error);
        }

        try{
            $user->update([
                'pin_code'      => $validated['new_pin'],
                'pin_status'    => true,
            ]);
        }catch(Exception $e){
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        $message =  ['success'=>[__('PIN updated successfully.')]];
        return Helpers::onlysuccess($message);
    }
}
