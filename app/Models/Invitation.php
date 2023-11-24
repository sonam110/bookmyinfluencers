<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Campaign;
use App\Models\user;
use App\Models\ChannelSearchData;
use App\Models\ChannelEngagement;
use App\Models\Favourite;
use App\Models\Revealed;
use App\Models\Channel;
use App\Models\ChannelAssociation;

class Invitation extends Model {

    use HasFactory;

    protected $fillable = [
        'brand_id',
        'camp_id',
        'influ_id',
        'channel_id',
        'message',
        'status',
    ];

    public function infInfo() {
        return $this->belongsTo(User::class, 'influ_id', 'id');
    }

    public function campInfo() {
        return $this->belongsTo(Campaign::class, 'camp_id', 'id');
    }

    public function channelInfo() {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

    protected $appends = ['channel'];

    public function getChannelAttribute() {
        $user = getUser();
        $channel = \DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','channel_email','blur_image','tag_category','tags','facebook','twitter','instagram','inf_recommend','price_view_ratio')->find($this->channel_id); //Channel::where('id', $this->channel_id)->first();
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

}
