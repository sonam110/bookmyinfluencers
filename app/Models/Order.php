<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Campaign;
use App\Models\user;
use App\Models\AppliedCampaign;
use App\Models\OrderProcess;
use Str;
use App\Models\ChannelSearchData;
use App\Models\ChannelEngagement;
use App\Models\Invitation;
use App\Models\Favourite;
use App\Models\Revealed;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use App\Models\InstagramData;

class Order extends Model {

    use HasFactory;

    protected $fillable = [
        'uuid',
        'application_id',
        'influ_id',
        'brand_id',
        'camp_id',
        'channel_id',
        'payment_term',
        'camp_price',
        'pay_amount',
        'currency',
        'tax',
        'job_description',
        'template_script',
        'tax_amount',
        'total_pay',
        'message',
        'deadlineDate',
        'new_deadlineDate',
        'payment_status',
        'plateform_type',
        'status',
    ];

    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function getCampPriceAttribute() {
        $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
        if($this->attributes['currency'] != $user_currency){

            return round($this->attributes['camp_price'] * auth()->user()->ops_currency_rate,2);
           
        } else {
            return $this->attributes['camp_price'];
        }
        
       
    }
    public function getPayAmountAttribute() {
        $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
        if($this->attributes['currency'] != $user_currency){
            return round($this->attributes['pay_amount'] * auth()->user()->ops_currency_rate,2);
          
        } else {
            return $this->attributes['pay_amount'];
        }
        
       
    }
    public function getTaxAmountAttribute() {
        $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
        if($this->attributes['currency'] != $user_currency){
            return round($this->attributes['tax_amount'] * auth()->user()->ops_currency_rate,2);
            
        } else {
            return $this->attributes['tax_amount'];
        }
       
       
    }
    public function getTotalPayAttribute() {
        $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
        if($this->attributes['currency'] != $user_currency){
            return round($this->attributes['total_pay'] * auth()->user()->ops_currency_rate,2);
            
        } else {
            return $this->attributes['total_pay'];
        }
       
    }
     public function orderProcess() {
        return $this->belongsTo(OrderProcess::class, 'id', 'order_id');
    }

    public function appInfo() {
        return $this->belongsTo(AppliedCampaign::class, 'application_id', 'id');
    }

    public function infInfo() {
        return $this->belongsTo(User::class, 'influ_id', 'id');
    }

    public function brandInfo() {
        return $this->belongsTo(User::class, 'brand_id', 'id');
    }

    public function campInfo() {
        return $this->belongsTo(Campaign::class, 'camp_id', 'id');
    }
   
   

    public function channelInfo() {
       
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
        
    }
    public function InstagramData() {
        return $this->belongsTo(InstagramData::class, 'channel_id', 'id');
       
    }

    /*protected $appends = ['channel'];

    public function getChannelAttribute() {
        $user = getUser();
        if($this->plateform_type =='2'){
            $channel = \DB::table('instagram_data')->find($this->channel_id);
            $channel_association = DB::table('channel_association')->where('internal_channel_id', $channel->id)->first();

            $influ_id = isset($channel_association->influ_id) ? $channel_association->influ_id : '2';

            $vidoe_promotion_price = isset($channel_association->promotion_price) ? $channel_association->promotion_price : 0;
            $is_favourite = false;
            $favourite = \DB::table('favourites')->where('user_id', $user->id)->where('channel_id', $channel->id)->where('plateform_type','2')->first();
            if (!empty($favourite)) {
                $is_favourite = true;
            }
            $is_revealed = false;
            $revealed =\DB::table('revealeds')->where('user_id', $user->id)->where('channel_id', $channel->id)->where('plateform_type','2')->first();
            if (!empty($revealed)) {
                $is_revealed = true;
            }
            $is_invite = 0;
            $invite = \DB::table('invitations')->where('id', $user->id)->where('influ_id', $influ_id)->where('channel_id', $channel->id)->where('plateform_type','2')->first();
            if (!empty($invite)) {
                $is_invite = 1;
            }
            $credit_cost = (!empty($channel->credit_cost)) ? $channel->credit_cost : 10;

            $channelList = [
                "id" => $channel->id,
                "influ_id" => $influ_id,
                "channel_id" => $channel->id,
                "channel_lang" => @$channel->language,
                "name" => $channel->name,
                "is_default" => (!empty($channel_association)) ? $channel_association->is_default : false,
                "is_verified" => (!empty($channel_association)) ? $channel_association->is_verified : '0',
                "promotion_price" => ($is_revealed == true) ? $vidoe_promotion_price : null,
                "username" => ($is_revealed == true) ? $channel->username : null,
                "bio" => ($is_revealed == true) ? $channel->bio : null,
                "followers" => $channel->followers,
                "following" => $channel->following,
                "profile_post" => $channel->profile_post,
                "tag_category" => $channel->tag_category,
                "category" => $channel->category,
                "image" => ($is_revealed == true) ? $channel->image : null,
                "blur_image" => $channel->blur_image,
                "is_invite" => $is_invite,
                "is_favourite" => $is_favourite,
                "is_revealed" => $is_revealed,
                "credit_cost" => $credit_cost,
                "channel_email" => ($is_revealed == true) ? $channel->emails : null,
                "inf_score" => ($is_revealed == true ) ? $channel->inf_score : null,
                "website" => ($is_revealed == true ) ? $channel->website : null,
                "inf_price" => ($is_revealed == true ) ? $channel->inf_price : null,
                "pitching_price" => ($is_revealed == true ) ? $channel->pitching_price : null,
                "story_price" => ($is_revealed == true ) ? $channel->story_price : null,
                "fair_price" => ($is_revealed == true ) ? $channel->fair_price : null,
                "number" => ($is_revealed == true ) ? $channel->phone : null,
                "is_email" => (!empty($channel->emails)) ? true : false,
                "is_number" => (!empty($channel->phone)) ? true : false,
                ];

                return $channelList;
        } else{
            $channel = \DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','channel_email','blur_image','tag_category','tags','facebook','twitter','instagram','inf_recommend','price_view_ratio')->find($this->channel_id);
            if ($channel) {

            $channel_association = ChannelAssociation::select('id','internal_channel_id','promotion_price','influ_id','is_default','is_verified')->where('internal_channel_id', $channel->id)->with('infInfo:id,detail_in_exchange,whats_app_notification')->first();
            $vidoe_promotion_price = (!empty($channel_association)) ? $channel_association->promotion_price : 0;

            $title = ($channel && isset($channel->channel_name)) ? $channel->channel_name : '';
            $description = $channel && isset($channel->yt_description) ? $channel->yt_description : '';
            $viewCount = $channel && isset($channel->views) ? $channel->views : '';
            $subscriberCount = $channel && isset($channel->subscribers) ? $channel->subscribers : '';
            $videoCount = $channel && isset($channel->videos) ? $channel->videos : '';

            $ViewsSubs = $channel && isset($channel->views_sub_ratio) ? $channel->views_sub_ratio : '';
            $EngagementRate = $channel && isset($channel->engagementrate) ? $channel->engagementrate . '%' : '';

            $EstViews = ($channel) ? $channel->exposure : '0';

            $price = ($EstViews != 0) ? $vidoe_promotion_price / $EstViews : 0;
            $ViewsPrice = round($price * 1000, 2);

            $ViewsPrice = $ViewsPrice;
            $is_revealed = false;
            $revealed = \DB::table('revealeds')->select('id')->where('user_id', $user->id)->where('channel_id', $channel->id)->first();
            if (!empty($revealed)) {
                $is_revealed = true;
            }
            $is_show = false;
            if ($user->userType == 'influencer') {
                $is_show = true;
            }
            if ($is_revealed == true && $user->userType == 'brand') {
                $is_show = true;
            }
            $is_invite = false;
            $invite = \DB::table('invitations')->select('id')->where('brand_id', $user->id)->where('channel_id', $channel->id)->first();
            if (!empty($invite)) {
                $is_invite = true;
            }
            $is_favourite = false;
            $favourite = \DB::table('favourites')->select('id')->where('user_id', $user->id)->where('channel_id', $channel->id)->first();
            if (!empty($favourite)) {
                $is_favourite = true;
            }
            $credit_cost = ($channel && isset($channel->credit_cost)) ? $channel->credit_cost : 0;
            $channelList = [
                "id" => $channel->id,
                "influ_id" => (!empty($channel_association)) ? $channel_association->influ_id : '2',
                "channel_id" => $channel->id,
                "channel" => $channel->channel,
                "channel_lang" => $channel->channel_lang,
                "channel_link" => ($is_show == true ) ? $channel->channel_link : null,
                "promotion_price" => ($is_show == true ) ? $vidoe_promotion_price : null,
                "is_default" => (!empty($channel_association)) ? $channel_association->is_default : false,
                "is_verified" => (!empty($channel_association)) ? $channel_association->is_verified : '0',
                "title" => ($is_show == true ) ? $title : null,
                "description" => ($is_show == true ) ? $description : null,
                "viewCount" => $viewCount,
                "subscriberCount" => $subscriberCount,
                "videoCount" => $videoCount,
                "ViewsSubs" => $ViewsSubs,
                "EngagementRate" => $EngagementRate,
                "EstViews" => $EstViews,
                "cpm" => ($is_show == true ) ? $ViewsPrice : null,
                "price_view_ratio" => ($is_show == true ) ? $channel->price_view_ratio : null,
                "currency" => $channel->currency,
                "cat_percentile_1" => ($is_show == true ) ? $channel->cat_percentile_1 : null,
                "cat_percentile_5" => ($is_show == true ) ? $channel->cat_percentile_5 : null,
                "cat_percentile_10" => ($is_show == true ) ? $channel->cat_percentile_10 : null,
                "profile_pic" => ($is_show == true ) ? $channel->image_path : null,
                "channel_email" => ($is_show == true ) ? $channel->channel_email : null,
                "blur_profile_pic" => $channel->blur_image,
                "tags" => tags($channel->tags),
                "tag_category" => $channel->tag_category,
                "is_revealed" => $is_revealed,
                "is_favourite" => $is_favourite,
                "is_invite" => $is_invite,
                "credit_cost" => $credit_cost,
                "facebook" => ($is_show == true) ? $channel->facebook : null,
                "twitter" => ($is_show == true) ? $channel->twitter : null,
                "instagram" => ($is_show == true) ? $channel->instagram : null,
                "inf_recommend" => ($is_show == true) ? $channel->inf_recommend : null,
                "influncerInfo" => @$channel_association->infInfo,
            ];

            return $channelList;
        }
        }
        
        
    }*/

}
