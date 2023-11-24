<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model {

    use HasFactory;

    protected $table = "subscription_plan";
   // protected $appends = ['amount'];
    protected $fillable = [
        'campaigns',
        'credits',
        'credit_rollover',
        'multi_user',
        'signup_bonus',
        'min_order_value',
        'dispute_resolution',
        'deal_coordination',
        'additional_credits',
        'order_payments',
        'premium',
        'customer_support',
        'campaign_approval',
        'whatsapp_introductions',
        'campaign_visibility',
        'campaign_approval_hrs',
        'price',
        'name',
        'status',
    ];
    public function getPriceAttribute() {
        if($this->attributes['id'] !='1'){
            $currency ='INR';
            $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
           
           
            if($currency != $user_currency){
                
                return round($this->attributes['price'] * auth()->user()->ops_currency_rate,2);
                
            } else {
                return $this->attributes['price'];
            }

        } else{
            return $this->attributes['price'];
        }
       
       
    }

    public function getMinOrderValueAttribute() {
        if($this->attributes['id'] !='1'){
            $currency ='INR';
            $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
            if($currency != $user_currency){
                return round($this->attributes['min_order_value'] * auth()->user()->ops_currency_rate,2);
               
            } else {
                return $this->attributes['min_order_value'];
            }
        } else{
            return $this->attributes['min_order_value'];
        }
       
    }
     public function getSignupBonusAttribute() {
        if($this->attributes['id'] !='1'){
            $currency ='INR';
            $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
            if($currency != $user_currency){
                return round($this->attributes['signup_bonus'] * auth()->user()->ops_currency_rate,2);
               
            } else {
                return $this->attributes['signup_bonus'];
            }
        } else{
            return $this->attributes['signup_bonus'];
        }
       
       
    }

   
    

}
