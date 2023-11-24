<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use DB;
use App\Models\Channel;
use App\Models\AppliedCampaign;
use App\Models\Order;
use App\Models\Revealed;
class TransactionHistory extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'status',
        'type',
        'bal_type',
        'amount',
        'old_amount',
        'new_amount',
        'status',
        'message',
        'comment',
        'resource',
        'resource_id',
        'created_by',
    ];

  public function userInfo() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
     protected $appends = ['data_info'];

    public function getDataInfoAttribute() {
        $resourseData = NULL;
        if($this->resource !=''){
            if($this->resource=='applied_campaigns'){
                $resourseData = DB::table('applied_campaigns')->where('id',$this->resource_id)->first();

            }
            elseif($this->resource=='revealeds'){
                $resourseData =  DB::table('revealeds')->select('revealeds.id','revealeds.channel_id','revealeds.user_id','channels.channel_name','channels.id')->where('revealeds.id',$this->resource_id)->join('channels','channels.id','revealeds.channel_id')->first();

            }
            elseif($this->resource=='orders'){
                $resourseData = DB::table('orders')->where('id',$this->resource_id)->first();

            } else{
                $resourseData = DB::table($this->resource)->where('id',$this->resource_id)->first();
            }
            
        }
        return $resourseData;
    }

    public function getAmountAttribute() {
        if($this->attributes['type']=='1'){

            $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
            if($this->currencyInfo() != $user_currency){
                return round($this->attributes['amount'] * auth()->user()->ops_currency_rate,2);
               
            } else {
                return $this->attributes['amount'];
            }


        } else{
            return $this->attributes['amount'];
        }
       
    }
    public function currencyInfo() {
        $resourseData = NULL;
        $currency = 'INR';
        if($this->resource !=''){
            if($this->resource=='applied_campaigns'){
                $resourseData = DB::table('applied_campaigns')->select('id','currency')->where('id',$this->resource_id)->first();
                $currency= (!empty($resourseData)) ? $resourseData->currency :'INR';

            }
            elseif($this->resource=='revealeds'){
                $resourseData = DB::table('revealeds')->select('id','currency')->where('id',$this->resource_id)->first();
                $currency= (!empty($this->attributes['currency'])) ? $this->attributes['currency'] :'INR';

            }
            elseif($this->resource=='payment_histories'){
                $resourseData = DB::table('payment_histories')->select('id','currency')->where('id',$this->resource_id)->first();
                $currency= (!empty($resourseData)) ? $resourseData->currency :'INR';

            }
            elseif($this->resource=='orders'){
                $resourseData =DB::table('orders')->select('id','currency')->where('id',$this->resource_id)->first();
                $currency= (!empty($resourseData)) ? $resourseData->currency :'INR';

            }elseif($this->resource=='subscriptions' || $this->resource=='subscription_topups' ){
                  $currency= (!empty($this->currency)) ? $this->currency :'INR';
            } else{
                $resourseData = DB::table($this->resource)->select('id','currency')->where('id',$this->resource_id)->first();
                $currency= (!empty($this->attributes['currency'])) ? $this->attributes['currency'] :'INR';
            }
            
        }
        return $currency;
       
    }


}
