<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Models\Admin\BasicSettings;
use App\Http\Controllers\Controller;
use App\Notifications\Admin\SendTestMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class SetupEmailController extends Controller
{

    /**
     * Display The Email Configuration Page
     *
     * @return view
     */
    public function configuration() {
        $page_title = __("Email Method");
        $email_config = BasicSettings::first()->mail_config;
        return view('admin.sections.setup-email.config',compact(
            'page_title',
            'email_config',
        ));
    }


    /**
     * Display The Email Default Template Page
     *
     * @return view
     */
    public function defaultTemplate() {
        $page_title = __("Default Template");
        return view('admin.sections.setup-email.default-template',compact(
            'page_title',
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'method'               => 'required|string|in:smtp,php,mailgun,reachead|max:20',
            'host'                 => 'required_if:method,smtp|nullable|string|max:255',
            'port'                 => 'required_if:method,smtp|nullable|numeric',
            'encryption'           => 'required_if:method,smtp|nullable|string|in:ssl,tls,auto|max:15',
            'username'             => 'required_if:method,smtp|nullable|string|max:250',
            'password'             => 'required_if:method,smtp|nullable|string|max:250',
            'from_address'         => 'required_if:method,smtp|nullable|string|max:250',
            'mailgun_host'         => 'required_if:method,mailgun|nullable|string|max:255',
            'mailgun_port'         => 'required_if:method,mailgun|nullable|numeric',
            'mailgun_encryption'   => 'required_if:method,mailgun|nullable|string|in:ssl,tls,auto|max:15',
            'mailgun_username'     => 'required_if:method,mailgun|nullable|string|max:250',
            'mailgun_password'     => 'required_if:method,mailgun|nullable|string|max:250',
            'mailgun_from_address' => 'required_if:method,mailgun|nullable|string|max:250',
            'mailgun_domain'       => 'required_if:method,mailgun|nullable|string|max:250',
            'mailgun_secret'       => 'required_if:method,mailgun|nullable|string|max:250',
            'reachead_from_address'=> 'required_if:method,reachead|nullable|string|max:250',
            'reachead_api_key'     => 'required_if:method,reachead|nullable|string|max:250',
        ]);

        $validated = $validator->validate();


        $basic_settings = BasicSettings::first();
        if(!$basic_settings) {
            return back()->with(['error' => [__('Basic settings not found!')]]);
        }

        // Make object of email template
        if($validated['method'] == "smtp") {
            $data = [
                'method'            => $validated['method'] ?? false,
                'host'              => $validated['host'] ?? false,
                'port'              => $validated['port'] ?? false,
                'encryption'        => $validated['encryption'] ?? false,
                'username'          => $validated['username'] ?? false,
                'password'          => $validated['password'] ?? false,
                'from'              => $validated['from_address'] ?? false,
                'app_name'          => $basic_settings['site_name'] ?? config("app.name"),
            ];
        }else if($validated['method'] == "mailgun") {
            $data = [
                'method'            => $validated['method'] ?? false,
                'host'              => $validated['mailgun_host'] ?? false,
                'port'              => $validated['mailgun_port'] ?? false,
                'encryption'        => $validated['mailgun_encryption'] ?? false,
                'username'          => $validated['mailgun_username'] ?? false,
                'password'          => $validated['mailgun_password'] ?? false,
                'from'              => $validated['mailgun_from_address'] ?? false,
                'app_name'          => $basic_settings['site_name'] ?? config("app.name"),
                'domain'            => $validated['mailgun_domain'],
                'secret'            => $validated['mailgun_secret'],
            ];
        }else if($validated['method'] == "reachead") {
            $data = [
                'method'            => $validated['method'] ?? false,
                'from'              => $validated['reachead_from_address'] ?? false,
                'api_key'           => $validated['reachead_api_key'] ?? false,
                'app_name'          => $basic_settings['site_name'] ?? config("app.name"),
            ];
        }else {
            $data = [];
        }


        try{
            $basic_settings->update([
                'mail_config'       => $data,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        $env_modify_keys = [
            "MAIL_MAILER"       => $data['method'] ?? "",
            "MAIL_HOST"         => $data['host'] ?? "",
            "MAIL_PORT"         => $data['port'] ?? "",
            "MAIL_USERNAME"     => $data['username'] ?? "",
            "MAIL_PASSWORD"     => $data['password'] ?? "",
            "MAIL_ENCRYPTION"   => $data['encryption'] ?? "",
            "MAIL_FROM_ADDRESS" => $data['from'] ?? "",
            "MAIL_FROM_NAME"    => $data['app_name'] ?? "",
        ];

        if($validated['method'] == 'mailgun') {
            $env_modify_keys['MAILGUN_DOMAIN']  = $data['domain'];
            $env_modify_keys['MAILGUN_SECRET']  = $data['secret'];
        }else {
            $env_modify_keys['MAILGUN_DOMAIN']  = "";
            $env_modify_keys['MAILGUN_SECRET']  = "";
        }

        if($validated['method'] == 'reachead') {
            $env_modify_keys['REACHEAD_API_KEY'] = $data['api_key'];
        }else {
            $env_modify_keys['REACHEAD_API_KEY'] = "";
        }

        try{
            modifyEnv($env_modify_keys);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Information updated successfully!')]]);
    }



    public function sendTestMail(Request $request) {
        $validator = Validator::make($request->all(),[
            'email'         => 'required|string|email',
        ]);

        $validated = $validator->validate();

        try{
            Notification::route('mail',$validated['email'])->notify(new SendTestMail());
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Email send successfully!')]]);
    }
}