<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Channel;
use App\Models\ChannelEngagement;
use DB;
class ChannelAssociation extends Model {

    use HasFactory;

    protected $table = 'channel_association';
    protected $casts = [
        'is_default' => 'boolean',
    ];
    protected $fillable = [
        'influ_id',
        'internal_channel_id',
        'is_verified',
        'promotion_price',
        'is_default',
    ];

    public function channelInfo() {
        $channel =  DB::table('channels')->where('id',$this->internal_channel_id)->first();
        return (!empty($channel)) ? $channel->currency :'INR';
    }

    public function infInfo() {
        return $this->belongsTo(User::class, 'influ_id', 'id');
    }

    public function getPromotionPriceAttribute() {
        if (\Auth::check()){
            $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
            if($this->channelInfo() != $user_currency){
                
                return round($this->attributes['promotion_price'] * auth()->user()->ops_currency_rate,2);
               
            } else {
                return $this->attributes['promotion_price'];
            }

        } else{
            return $this->attributes['promotion_price'];
        }

    }

}
