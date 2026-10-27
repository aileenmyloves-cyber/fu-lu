<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\User;
use App\Models\Chatbox;
use Illuminate\Support\Arr;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\User\ConversationEvent;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\Api\Helpers;

class ChatController extends Controller
{
    public function index()
    {
        $chatBox =  Chatbox::with('user')->where('sender_id',Auth::user()->id)->orWhere('receiver_id',Auth::user()->id)->paginate(10);
        $data = [
            'chatBox' => $chatBox
        ];

        $message =  ['success'=> [__('Add Money Information!')]];
        return Helpers::success($data,$message);
    }


    public function userCheck(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->mobile == $request->phone || $authUser->full_mobile == $request->phone || $authUser->email == $request->email) {
            $error = ['error'=>[__('You cannot chat with yourself!')]];
            return Helpers::error($error);
        }

        $user = User::where('mobile',$request->phone)->orWhere('full_mobile',$request->phone)->orWhere('email',$request->email)->first();
        if($user == null) {
            $error = ['error'=>[__('User not found')]];
            return Helpers::error($error);
        }

        $data = [
            'receiver_id' => $user->id ?? "",
            'name' => $user->fullname ?? "",
            'email' => $user->email ?? "",
            'mobile' => $user->mobile ?? "",
            'full_mobile' => $user->full_mobile ?? "",
        ];

        $message = ['success' =>  [__('User found')]];
        return Helpers::success($data,$message);




    }


    public function create(Request $request)
    {

        if ($request->receiver_id == Auth::id()) {
            $message = ['error' =>  [__('You cannot chat with yourself!')]];
            return Helpers::error($message);
        }


        $validator = Validator::make($request->all(),[
            'receiver_id'           => "required|integer",
        ]);

        $user = Auth::user();
        $chat_box = Chatbox::where(function ($query) use ($user, $request) {
            $query->where('sender_id', $user->id)
                  ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($query) use ($user, $request) {
            $query->where('sender_id', $request->receiver_id)
                  ->where('receiver_id', $user->id);
        })->first();

        if ($chat_box) {
            $data = ['Chatbox_id' => $chat_box->id ];
            $message = ['success' =>  [__('ChatBox id fetch Successfully')]];
            return Helpers::success($data,$message);

        }

        $user = Auth::user();

        $validated               = $validator->validate();
        $validated['sender_id']    = $user->id;
        $validated['receiver_id']    = $request->receiver_id;
        $validated['status']     = 1;
        $validated['created_at'] = now();
        $validated               = Arr::except($validated,['attachment']);

        try{
            $chatBox_id = Chatbox::insertGetId($validated);
            $email = $user->email;

        }catch(Exception $e) {
            $error = ['error'=>[__('Something went wrong! Please try again.')]];
            return Helpers::error($error);
        }

        $data = ['Chatbox_id' => $chatBox_id ];
        $message = ['success' =>  [__('ChatBox id fetch Successfully')]];
        return Helpers::success($data,$message);

    }



    public function conversation(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'Chatbox_id'           => "required|integer",
        ]);

        $validated               = $validator->validate();
        $chatbox_id = $validated['Chatbox_id'];
        $chatBox = Chatbox::with('conversations')->where('id',$chatbox_id)->first();
        if (!$chatBox) {
            $error = ['error' => [__('ChatBox not found')]];
            return Helpers::error($error);
        }

        $chats = $chatBox->conversations->map(function($data){
            return[
                'id'             => $data->id,
                'sender'         => $data->sender,
                'message_sender' => $data->sender == auth()->user()->id ? "own" : "opposite",
                'message'        => $data->message,
                'profile_img' => $data->senderImage,
            ];
        });
        $data =[
            'chatBox'                   => $chatbox_id,
            'property_conversations'    => $chats,
            'message_send'              => url('api/user/p2p/chat/message/send'),
            'method'                    => "post",
            'created_at'                => $chatBox->created_at->format("Y-m-d H:i A"),
        ];
        $message = ['success'=>[__('Chatting Conversation Information')]];
        return Helpers::success($data,$message);
    }




    public function messageSend(Request $request) {
        $validator = Validator::make($request->all(),[
            'chatBox_id' => 'required|integer',
            'message'       => 'required|string|max:200',
        ]);
        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Helpers::error($error);
        }
        $validated = $validator->validate();
        $chat_Box = Chatbox::where('id',$request->chatBox_id)->first();
        if(!$chat_Box){
            $error = ['error' => [__('Not found')]];
            return Helpers::error($error);
        }

        $data = [
            'chatbox_id'    => $chat_Box->id,
            'sender'        => auth()->user()->id,
            'sender_type'   => "USER",
            'receiver'      => $chat_Box->receiver_id,
            'message'       => $validated['message'],
            'receiver_type' => "USER",
        ];

        try{
            $chat_data = Conversation::create($data);
        }catch(Exception $e) {
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Helpers::error($error,null,500);
        }

        try{
            event(new ConversationEvent($chat_Box,$chat_data));
        }catch(Exception $e) {
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Helpers::error($error);
        }

        $success = ['success' => [__('Message sent successfully')]];
        return Helpers::onlysuccess($success);
    }


}
