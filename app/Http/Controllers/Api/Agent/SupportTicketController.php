<?php

namespace App\Http\Controllers\Api\Agent;

use App\Events\Admin\SupportConversationEvent;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Models\UserSupportChat;
use App\Models\UserSupportTicket;
use App\Models\UserSupportTicketAttachment;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function getTickets(Request $request){
        $basic_settings = BasicSettingsProvider::get();
        if(!$basic_settings) {
            $message = ['error'=>[__("Basic setting not found!")]];
            return Helpers::error($message);
        }

        $broadcast_config = $basic_settings->broadcast_config;

        $pusher_broadcast_config = [
            "method"          => $broadcast_config->method ?? '',
            "app_id"          => $broadcast_config->app_id ?? '',
            "primary_key"     => $broadcast_config->primary_key ?? '',
            "secret_key"      => $broadcast_config->secret_key ?? '',
            "cluster"         => $broadcast_config->cluster ?? '',
            "support_channel" => 'support.conversation.{$support_ticket->token}',
            "support_event"   => 'support-conversation',
        ];
        

        $support_tickets = UserSupportTicket::authTicketsAgent()->orderByDesc("id")->paginate(10);
        $support_tickets = $support_tickets->makeHidden(['user_id','admin_id','merchant_id','agent_id','imagePath','user','attachments'])->toArray();
        $data = [
            'pusher_broadcast_config' => (object)$pusher_broadcast_config,
            'support_tickets'         => $support_tickets,
        ];

        $message = ['success' => [__('Support Tickets Get Successfully').'!']];
        return Helpers::success($data, $message);

    }
    public function conversation(Request $request){
         $validator = Validator::make(request()->all(), [
            'token'     => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validated();
        

        $support_ticket = UserSupportTicket::authTicketsAgent()->where("token", $validated['token'])->first();
        if(!$support_ticket){
            $message = ['error'=>[__("Support ticket not found!")]];
            return Helpers::error($message);
        }
        $data = [
            'base_url'       => get_asset_url('/'),
            'default_image'  => files_asset_path_basename('default'),
            'image_path'     => files_asset_path_basename('support-attachment'),
            'support_ticket' => $support_ticket->makeHidden(['user_id','admin_id','merchant_id','agent_id','imagePath','user','attachments'])->toArray(),
            'attachments'    => $support_ticket->attachments()->get()->makeHidden(['attachment_info'])->toArray(),
            'conversations'  => $support_ticket->conversations()->get()->makeHidden(['supportTicket'])->toArray(),
        ];

        $message = ['success' => [__('Conversations Get Successfully').'!']];
        return Helpers::success($data, $message);

    }

    public function storeTicket(Request $request){
        $validator = Validator::make($request->all(), [
            'name'              => "required|string|max:60",
            'email'             => "required|string|email|max:150",
            'subject'           => "required|string|max:255",
            'desc'              => "nullable|string|max:5000",
            'attachment.*'      => "nullable|file|max:204800",
        ]);

        if($validator->fails()){
            $error =  ['error'=> $validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validated();

        $validated               = $validator->validate();
        $validated['token']      = "ST".getTrxNum();
        $validated['agent_id']   = auth()->user()->id;
        $validated['status']     = 0;
        $validated['created_at'] = now();
        $validated               = Arr::except($validated,['attachment']);

        try{
            $support_ticket_id = UserSupportTicket::insertGetId($validated);
        }catch(Exception $e) {
            $message = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($message);
        }

        if($request->hasFile('attachment')) {
            $validated_files = $request->file("attachment");
            $attachment = [];
            $files_link = [];
            foreach($validated_files as $item) {
                $upload_file = upload_file($item,'support-attachment');
                if($upload_file != false) {
                    $attachment[] = [
                        'user_support_ticket_id'    => $support_ticket_id,
                        'attachment'                => $upload_file['name'],
                        'attachment_info'           => json_encode($upload_file),
                        'created_at'                => now(),
                    ];
                }

                $files_link[] = get_files_path('support-attachment') . "/". $upload_file['name'];
            }

            try{
                UserSupportTicketAttachment::insert($attachment);
            }catch(Exception $e) {
                $support_ticket_id->delete();
                delete_files($files_link);
                
                $message = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($message);
            }
        }
        
        $message = ['success' => [__('Support ticket created successfully!')]];
        return Helpers::onlysuccess( $message);

    }
    public function sendMessage(Request $request){
        $validator = Validator::make($request->all(), [
            'message'       => 'required|string|max:200',
            'support_token' => 'required|string',
        ]);

        if($validator->fails()){
            $error =  ['error'=> $validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validated();

        $support_ticket = UserSupportTicket::notSolved($validated['support_token'])->first();
        if(!$support_ticket){
            $message = ['error'=>[__("This support ticket is closed.")]];
            return Helpers::error($message);
        } 

        $data = [
            'user_support_ticket_id'    => $support_ticket->id,
            'sender'                    => auth()->user()->id,
            'sender_type'               => "AGENT",
            'message'                   => $validated['message'],
            'receiver_type'             => "ADMIN",
        ];

        try{
            $chat_data = UserSupportChat::create($data);
        }catch(Exception $e) {
            $message = ['error'=>[__("SMS Sending failed! Please try again.")]];
            return Helpers::error($message);
        }

        try{
            event(new SupportConversationEvent($support_ticket,$chat_data));
        }catch(Exception $e) {
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Helpers::error($error);
        }

       
        
        $message = ['success' => [__('Message sent successfully!')]];
        return Helpers::onlysuccess( $message);

    }
}