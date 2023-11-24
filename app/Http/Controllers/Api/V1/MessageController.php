<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Validator;
use Auth;
use Except;
use DB;

class MessageController extends Controller {

    public function allUsers(Request $request) {

       try {
            $user = getUser();
            $user_id = $user->id;

            if($user->userType =='brand'){
                $latestMessagesSubquery = Message::select(DB::raw("MAX(id) as max_id"))
                    ->where(function ($query) use ($user_id) {
                        $query->where('sender_id', $user_id)
                            ->orWhere('receiver_id', $user_id);
                    })
                    ->groupBy('channel_id')->pluck('max_id')->toArray();
            } else{
                    $latestMessagesSubquery = Message::select(DB::raw("MAX(id) as max_id"))
                    ->where(function ($query) use ($user_id) {
                        $query->where('receiver_id', $user_id);
                    })
                    ->groupBy('messages.channel_id','messages.sender_id','messages.receiver_id')->pluck('max_id')->toArray();

            }

            $users = Message::select(
                    'messages.*', 
                    'sender.id as senderid',
                    'sender.fullname as sender_fullname',
                    'sender.profile_photo as sender_profile_photo',
                    'sender.created_at as sender_created_at',
                    'receiver.id as receiverid',
                    'receiver.fullname as receiver_fullname',
                    'receiver.profile_photo as receiver_profile_photo',
                    'receiver.created_at as receiver_created_at'
                )
                ->join('users as sender', 'messages.sender_id', '=', 'sender.id')
                ->join('users as receiver', 'messages.receiver_id', '=', 'receiver.id')
                ->whereIn('messages.id', $latestMessagesSubquery)
                ->orderBy('messages.is_read', 'asc')
                ->orderBy('messages.id', 'DESC')
                ->get();

            return prepareResult(true, "User List", $users, $this->success);
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function newChat(Request $request) {

        try {
            $user = getUser();
            $validator = Validator::make($request->all(), [
                        'user_id' => 'required',
                            ],
                            [
                                'user_id.required' => 'User id field is required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $checkUser = User::where('id', $request->user_id)->first();
            if (!is_object($checkUser)) {
                return prepareResult(false, "User Not Found", [], $this->not_found);
            }

            $user_id = $request->user_id;
            $query = Message::select(array('messages.*', DB::raw("(SELECT count(*) from messages WHERE messages.is_read = '0') unreadCount"), DB::raw("(SELECT message from messages ORDER BY id DESC LIMIT 1) lastMessage"), DB::raw("(SELECT created_at from messages ORDER BY id DESC LIMIT 1) lastMessageAt"), DB::raw('DATE(created_at) as date')))
                    ->where(function ($query) use ($user,$user_id) {
                        $query->where('sender_id', $user->id)
                            ->where('receiver_id', $user_id)
                            ->orWhere('sender_id', $user_id)
                            ->where('receiver_id', $user->id);;
                    })
                    ->where('channel_id',$request->channel_id)
                    ->where('plateform_type',$request->plateform_type)
                    ->with('contact:id,fullname,profile_photo')
                    ->orderBy('id','DESC')
                    ->first();
            $contact = DB::table('users')->select('id', 'fullname', 'profile_photo')->where('id', $request->user_id)->first();
           
            $channel_detail = [];
            if($request->plateform_type =='2'){
                $channel = \DB::table('instagram_data')->find($request->channel_id);
                 if ($channel) {
                    $channel_detail = [
                        "id" => $channel->id,
                        "channel_id" => $channel->id,
                        "fullname" =>  $channel->username ,
                        "profile_photo" =>  $channel->image ,
                        "plateform_type" =>  '2' ,

                       
                        ];

                }
            } else{
                $channel = \DB::table('channels')->select('id','channel_name','image_path')->find($request->channel_id);
                if ($channel) {
                    $channel_detail = [
                        "id" => $channel->id,
                        "channel_id" => $channel->id,
                        "fullname" => $channel->channel_name,
                        "profile_photo" => $channel->image_path,
                        "plateform_type" =>  '1' ,
                  
                    ];

                }
            }
            $messages=  Message::where(function ($messages) use ($user,$user_id) {
                $messages->where('sender_id', $user->id)
                       ->where('receiver_id', $user_id)
                       ->orWhere('sender_id', $user_id)
                       ->where('receiver_id', $user->id);
        
            })
            ->where('channel_id',$request->channel_id)
            ->where('plateform_type',$request->plateform_type)
            ->orderBy('id', 'ASC')->get();
           
            $history = [];
            foreach ($messages as $key => $data) {
                $isMine = true;
                if ($user->id != $data->sender_id) {
                    $isMine = false;
                }

                $history[] = [
                    'id' => $data->id,
                    'isMine' => $isMine,
                    'createdAt' => $data->created_at,
                    'value' => $data->message,
                ];
            }
            $output = [
                'contact' => isset($contact) ? $contact : null,
                'message' => $history,
                'unreadCount' => isset($query->unreadCount) ? $query->unreadCount : null,
                'lastMessage' => isset($query->lastMessage) ? $query->lastMessage : null,
                'lastMessageAt' => isset($query->lastMessageAt) ? $query->lastMessageAt : null,
                'channel' => $channel_detail,
            ];
            $isReadAll = Message::where(function ($users) use ($user) {
                $users->where('sender_id', $user->id)
                    ->Orwhere('receiver_id', $user->id);
            })->where('channel_id',$request->channel_id)->update(['is_read' => '1']);
            return prepareResult(true, "Message List", $output, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }
    /*------Help Chat-------------*/
    public function helpMsg(Request $request) {

        try {
            $user = getUser();
           
            $checkMessage = Message::where('sender_id','2')->where('receiver_id',$user->id)->count();
            if($checkMessage <=0){
                $message = new Message();
                $message->sender_id = '2';
                $message->receiver_id = $user->id;
                $message->message = 'Welcome to bookmyinfluencers| How may I help you?';
                $message->is_read = '1';
                $message->save();
                return prepareResult(true, "Message", $message, $this->success);

            } else{
                 return prepareResult(true, "Message",[], $this->success);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
