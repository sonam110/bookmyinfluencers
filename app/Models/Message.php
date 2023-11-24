<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderProcess;
use App\Models\Channel;

class Message extends Model {

    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'order_id',
        'message',
        'is_read',
        'channel_id',
        'plateform_type',
        'is_system_generated',
        'status',
    ];

    public function sender() {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }
   

    public function receiver() {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }

    public function contact() {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }

   
    protected $appends = ['channel'];

    public function getChannelAttribute() {
        $user = getUser();
        $lastMessageHistory = \DB::table('messages')->select('id','message','created_at','is_read','sender_id','receiver_id','channel_id')->where(function ($users) use ($user) {
                $users->where('sender_id', $user->id)
                    ->Orwhere('receiver_id', $user->id);
            })->where('channel_id',$this->channel_id)->orderby('id','DESC')->first();

        if($this->plateform_type =='2'){
            $channel = \DB::table('instagram_data')->select('id','username','image')->find($this->channel_id);
             if ($channel) {
                $channelList = [
                    "message_id" => $lastMessageHistory->id,
                    "id" => $channel->id,
                    "channel_id" => $channel->id,
                    "fullname" =>  $channel->username ,
                    "profile_photo" =>  $channel->image ,
                    "plateform_type" =>  '2' ,
                    "lastMessage" =>  @$lastMessageHistory->message ,
                    "lastMessageAt" =>  @$lastMessageHistory->created_at ,
                    "is_read" =>  @$lastMessageHistory->is_read ,
                   
                    ];

                    return $channelList;
            }
        } else{
            $channel = \DB::table('channels')->select('id','channel_name','image_path')->find($this->channel_id);
            if ($channel) {
           
                $channelList = [
                    "message_id" => $lastMessageHistory->id,
                    "id" => $channel->id,
                    "channel_id" => $channel->id,
                    "fullname" => $channel->channel_name,
                    "profile_photo" => $channel->image_path,
                    "plateform_type" =>  '1' ,
                     "lastMessage" =>  @$lastMessageHistory->message ,
                    "lastMessageAt" =>  @$lastMessageHistory->created_at ,
                    "is_read" =>  @$lastMessageHistory->is_read ,

                ];

                return $channelList;
            }
        }
        
        
    }

}
