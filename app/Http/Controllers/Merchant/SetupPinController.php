<?php

namespace App\Http\Controllers\Merchant;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\BasicSettings;
use Illuminate\Support\Facades\Validator;

class SetupPinController extends Controller
{
    /**
     * Method for show setup page
     * @return view
     */
    public function index(){
        $page_title = __("Setup PIN");
        $basic_setting = BasicSettings::first();
        if( $basic_setting->merchant_pin_verification == false) return back()->with(['warning' => [__("PIN verification is temporarily unavailable.")]]);
        return view('merchant.sections.setup-pin.index',compact('page_title'));
    }
    /**
     * Method for store pin information
     * @param Illuminate\Http\Request $request
     */
    public function store(Request $request){
        $validator      = Validator::make($request->all(),[
            'pin_code'  => 'required|digits:4',
        ]);
        if($validator->fails()){
            return back()->withErrors($validator)->withInput($request->all());
        }
        $validated  = $validator->validated();
        $user       = auth()->user();
        try{
            $user->update([
                'pin_code'  => $validated['pin_code'],
                'pin_status'    => true,
            ]);
        }catch(Exception $e){
            return back()->with(['error' => ['Something went wrong! Please try again.']]);
        }
        return back()->with(['success' => ['Your PIN has been set up successfully.']]);
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
            return back()->withErrors($validator)->withInput($request->all());
        }
        $validated  = $validator->validated();
        $user       = auth()->user();
        if($validated['old_pin'] != $user->pin_code){
            return back()->with(['error' => [__("Invalid old PIN entered.")]]);
        }
        if($validated['new_pin'] == $user->pin_code){
            return back()->with(['error' => [__("New PIN must be different from the old PIN.")]]);
        }
        try{
            $user->update([
                'pin_code'  => $validated['new_pin'],
                'pin_status'    => true,
            ]);
        }catch(Exception $e){
            return back()->with(['error' => ['Something went wrong! Please try again.']]);
        }
        return back()->with(['success' => [__("PIN updated successfully.")]]);
    }
}
