<?php

namespace App\Http\Controllers\User;
use Exception;
use App\Models\User;
use App\Models\Chatbox;
use Illuminate\Support\Arr;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\User\ConversationEvent;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{

    public function index()
    {
        $page_title = __("My Chat");
        $chatBox =  Chatbox::with('user')->where('sender_id',Auth::user()->id)->orWhere('receiver_id',Auth::user()->id)->paginate(10);
        return view('user.sections.chat.index',compact('page_title','chatBox'));
    }



    public function userCheck(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->mobile == $request->phone || $authUser->full_mobile == $request->phone || $authUser->email == $request->email) {
            return response()->json(['status' => 400, 'message' => __('You cannot chat with yourself!')]);
        }

        $user = User::where('mobile',$request->phone)->orWhere('full_mobile',$request->phone)->orWhere('email',$request->email)->first();

        if($user == null) {
            return response()->json(['status' => 404,'message' => __('User not found').'!']);
        }

        return response()->json(['status' => 200,'message' => __('User found'),'data' => $user]);



    }


    public function create(Request $request)
    {

        if ($request->receiver_id == Auth::id()) {
            return response()->json(['status' => 400, 'message' => __('You cannot chat with yourself!')]);
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
            return redirect()->route('user.p2p.chat.conversation', $chat_box->id);
        }

        $user = Auth::user();

        $validated               = $validator->validate();
        // $validated['token']      = generate_unique_string('user_support_tickets','token');
        $validated['sender_id']    = $user->id;
        $validated['receiver_id']    = $request->receiver_id;
        $validated['status']     = 1;
        $validated['created_at'] = now();
        $validated               = Arr::except($validated,['attachment']);

        try{
            $support_ticket_id = Chatbox::insertGetId($validated);
            $email = $user->email;

        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }


        return redirect()->route('user.p2p.chat.conversation',$support_ticket_id)->with(['success' => [__('Chat Created Successfully').'!']]);
    }


    public function conversation($chatbox_id)
    {
        $page_title = ("My Chat");
        $chatBox = Chatbox::with('conversations')->findOrFail($chatbox_id);
        return view('user.sections.chat.cnversation',compact('page_title','chatBox'));
    }

    public function messageSend(Request $request) {

        $validator = Validator::make($request->all(),[
            'chatBox' => 'required|integer',
            'message'       => 'required|string|max:200',
            // 'support_token' => 'required|string',
        ]);
        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->validate();

        $chat_Box = Chatbox::findOrFail($request->chatBox);
        if(!$chat_Box) return Response::error(['error' => [__('Not found')]],null,404);

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
            return Response::error($error,null,500);
        }

        try{
            event(new ConversationEvent($chat_Box,$chat_data));
        }catch(Exception $e) {
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Response::error($error,null,500);
        }
    }



}