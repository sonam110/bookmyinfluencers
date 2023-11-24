<?php

use App\Models\User;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use Illuminate\Http\Request;
use App\Models\TransactionHistory;
use App\Models\Message;
use App\Models\OrderProcess;
use App\Models\Subscriptions;
use voku\helper\HtmlDomParser;
use App\Models\Favourite;
use App\Models\Revealed;
use App\Models\Invitation;
use AmrShawky\LaravelCurrency\Facade\Currency;
function getUser() {
    return auth('api')->user();
}

//==================== For Api ================//
//---------Api Msg/status-----------//
function prepareResult($status, $message, $payload, $status_code) {
    if (empty($payload)) {
        $payload = new stdClass();
    } else {
        $payload = $payload;
    }
    return response()->json(['success' => $status, 'message' => $message, 'payload' => $payload, 'code' => $status_code], $status_code);
}

function parse_channel_id(string $url, \Exception $e): ?string {
    $parsed = parse_url(rtrim($url, '/'));
    if (isset($parsed['path']) && preg_match('/^\/channel\/(([^\/])+?)$/', $parsed['path'], $matches)) {
        return $matches[1];
    }

    throw new Exception("{$url} is not a valid YouTube channel URL");
    return null;
}

function checkYoutubeUrlValid($url) {
    try {
    $channelId = '';
    $dom = HtmlDomParser::file_get_html($url, false, null, 0);

    if (null !== ($dom->findOne('meta[itemprop=channelId]', 0))) {
        $channelId = $dom->findOne('meta[itemprop=channelId]', 0)->content;
    }
        return $channelId;
    } catch (\Exception $exception) {
      return null;
    }
}
function checkInstagramUrlValid($url) {
    try {
    $profile_url = 'sfsf';
    $dom = HtmlDomParser::file_get_html($url, false, null, 0);
   
    if (null !== ($dom->findOne('meta[property=og:url]', 0))) {

        $profile_url = $dom->findOne('meta[property=og:url]', 0)->content;
    }
    print_r($profile_url);
        return $profile_url;
    } catch (\Exception $exception) {
      return null;
    }
}

function transactionHistory($transaction_id, $user_id, $type, $message, $bal_type, $old_amount, $amount, $new_amount, $status, $comment, $created_by, $resource, $resource_id,$currency,$payment_mode) {
    $history = new TransactionHistory;
    $history->transaction_id = $transaction_id;
    $history->user_id = $user_id;
    $history->type = $type;
    $history->message = $message;
    $history->bal_type = $bal_type;
    $history->old_amount = $old_amount;
    $history->amount = $amount;
    $history->new_amount = $new_amount;
    $history->status = $status;
    $history->comment = $comment;
    $history->created_by = $created_by;
    $history->resource = $resource;
    $history->resource_id = $resource_id;
    $history->currency = $currency;
    $history->payment_mode = $payment_mode;
    $history->save();
    if ($history) {
        return $history->id;
    }
    return false;
}

function balanceUpdate($type, $bal_type, $user_id, $amount) {

    $user = User::find($user_id);
    if ($bal_type == '1') {
        if ($type == '1') {
            $user->wallet_balance = $user->wallet_balance + $amount;
        }
        if ($type == '2') {
            $user->credit_balance = $user->credit_balance + $amount;
        }
    }
    if ($bal_type == '2') {
        if ($type == '1') {
            $user->wallet_balance = $user->wallet_balance - $amount;
        }
        if ($type == '2') {
            $user->credit_balance = $user->credit_balance - $amount;
        }
    }

    $user->save();
    if ($user) {
        return $user->id;
    }
    return false;
}

function message($order_id, $sender_id, $receiver_id, $message, $is_read, $status,$channel_id,$plateform_type) {
    $messageHistory = new Message;
    $messageHistory->order_id = $order_id;
    $messageHistory->sender_id = $sender_id;
    $messageHistory->receiver_id = $receiver_id;
    $messageHistory->message = $message;
    $messageHistory->is_read = $is_read;
    $messageHistory->status = $status;
    $messageHistory->channel_id = $channel_id;
    $messageHistory->plateform_type = $plateform_type;
    $messageHistory->save();
    if ($messageHistory) {
        return $messageHistory->id;
    }
    return false;
}

function bearerToken(Request $request) {
    $header = $request->header('Authorization');
    if (Str::startsWith($header, 'Bearer ')) {
        return Str::substr($header, 7);
    }
}

function checkUserToken($token) {
    $result = [];
    // break up the token_name(token)en into its three parts
    $token_parts = explode('.', $token);
    if (is_array($token_parts) && array_key_exists('1', $token_parts)) {
        $token_header = $token_parts[1];
    } else {
        $token_header = null;
    }

    // base64 decode to get a json string
    $token_header_json = base64_decode($token_header);

    // then convert the json to an array
    $token_header_array = json_decode($token_header_json, true);

    $user_token = (is_array($token_header_array) && array_key_exists('jti', $token_header_array)) ? $token_header_array['jti'] : null;
    // find the user ID from the oauth access token table
    // based on the token we just got
    if ($user_token) {
        $user_id = DB::table('oauth_access_tokens')->where('id', $user_token)->first();
        $user_id = $user_id->user_id;
        $result = [
            "user_token" => $user_token,
            "user_id" => $tokenUser,
        ];
        return $result;
    } else {
        return $result;
    }
}

function mailSendTo($user_id) {
    $user = User::where('id', $user_id)->first();
    if ($user) {
        if ($user->account_type == '1') {
            $email = $user->email;
        } else {
            $email = env('MANAGER_MAIL', 'abhishek@prchitects.com');
        }
    } else {
        $email = env('MANAGER_MAIL', 'abhishek@prchitects.com');
    }
    return $email;
}

function currencyConvert($from,$to,$amount){
    $currency =    Currency::convert()
                ->from($from)
                ->to($to)
                ->amount($amount)
                ->round(2)
                ->get();

    $curr = (!empty($currency)) ? $currency:$amount;
    return  $curr;
}

function changePrice($price,$from){
    $amount = $price;
    if (\Auth::check()) {
        $to = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
        $from = (!empty($from)) ? $from :'INR';
        $currency =  currencyConvert($from,$to,$amount);
        $amount = $currency;

    }   
    return $amount;
}

function base64url_encode($str) {
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}

function base64url_decode($str) {
    return base64_decode(strtr($str, '-_', '+/'));
}

function createUserFreePlan($user_id) {
    $currentDate = now();
    $dateOneYearAdded = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($currentDate)));
    $subscriptions = Subscriptions::create([
                'subscription_plan_id' => '1',
                'user_id' => $user_id,
                'amount' => 0,
                'subscribed_at' => $currentDate,
                'expire_at' => $dateOneYearAdded,
                'status' => 1,
    ]);

    $updateUserCredit = DB::table('users')
        ->where('id', $user_id)
        ->update(['credit_balance' => '250']);
}

function tags($channel_tags){
    $tags = NULL;
    if(!empty($channel_tags)){
        $json = $channel_tags;
        $jsondecode = json_decode($json , true);
        if(!empty($jsondecode) && is_array($jsondecode)) {
            $keys = array_keys($jsondecode);
            $tags = implode(',',$keys);
        }
       
    }
    
    return $tags;
}

function youtubeData($channelList,$name) {
    $user = getUser();
    $channel_list =[];
    foreach ($channelList as $key => $channel) {
        $channel_association = DB::table('channel_association')->where('internal_channel_id', $channel->id)->first();

        $influ_id = isset($channel_association->influ_id) ? $channel_association->influ_id : '2';

        $vidoe_promotion_price = isset($channel_association->promotion_price) ? $channel_association->promotion_price : 0;

        $is_favourite = false;
        $favourite = \DB::table('favourites')->where('user_id', $user->id)->where('channel_id', $channel->id)->where('plateform_type','1')->first();
        if (!empty($favourite)) {
            $is_favourite = true;
        }
        $is_revealed = false;
        $revealed =\DB::table('revealeds')->where('user_id', $user->id)->where('channel_id', $channel->id)->where('plateform_type','1')->first();
        if (!empty($revealed)) {
            $is_revealed = true;
        }
        $title = ($channel && isset($channel->channel_name)) ? $channel->channel_name : '';
        $description = ($channel && isset($channel->yt_description)) ? $channel->yt_description : '';
        $viewCount = ($channel && isset($channel->views)) ? $channel->views : '';
        $subscriberCount = ($channel && isset($channel->subscribers)) ? $channel->subscribers : '';
        $videoCount = ($channel && isset($channel->videos)) ? $channel->videos : '';

        $ViewsSubs = ($channel && isset($channel->views_sub_ratio)) ? $channel->views_sub_ratio : '';
        $EngagementRate = ($channel && isset($channel->engagementrate)) ? $channel->engagementrate . '%' : '';

        $EstViews = ($channel && isset($channel->exposure)) ? $channel->exposure : 0;

        $price = ($EstViews != 0) ? $vidoe_promotion_price / $EstViews : 0;
        $ViewsPrice = round($price * 1000, 2);

        $ViewsPrice = $ViewsPrice;
        $is_invite = 0;
        $invite = \DB::table('invitations')->where('id', $user->id)->where('influ_id', $influ_id)->where('channel_id', $channel->id)->where('plateform_type','1')->first();
        if (!empty($invite)) {
            $is_invite = 1;
        }
        $credit_cost = (!empty($channel->credit_cost)) ? $channel->credit_cost : 10;

        $channel_list[] = [
            "id" => $channel->id,
            "influ_id" => $influ_id,
            "channel_id" => $channel->channel_id,
            "channel_lang" => $channel->channel_lang,
            "channel_name" => $channel->channel_name,
            "channel" => ($is_revealed == true) ? $channel->channel : null,
            "is_default" =>  $channel_association->is_default ,
            "is_verified" =>  $channel_association->is_verified ,
            "channel_link" => ($is_revealed == true) ? $channel->channel_link : null,
            "promotion_price" => ($is_revealed == true) ? $vidoe_promotion_price : null,
            "title" => ($is_revealed == true) ? $title : null,
            "description" => ($is_revealed == true) ? $description : null,
            "viewCount" => $viewCount,
            "subscriberCount" => $subscriberCount,
            "videoCount" => $videoCount,
            "ViewsSubs" => $ViewsSubs,
            "EngagementRate" => $EngagementRate,
            "EstViews" => $EstViews,
            "cpm" => ($is_revealed == true) ? $ViewsPrice : null,
            "tags" => tags($channel->tags),
            "tag_category" => $channel->tag_category,
            "cat_percentile_1" => ($is_revealed == true) ? $channel->cat_percentile_1 : null,
            "cat_percentile_5" => ($is_revealed == true) ? $channel->cat_percentile_5 : null,
            "cat_percentile_10" => ($is_revealed == true) ? $channel->cat_percentile_10 : null,
            "profile_pic" => ($is_revealed == true) ? $channel->image_path : null,
            "blur_image" => $channel->blur_image,
            "is_invite" => $is_invite,
            "is_favourite" => $is_favourite,
            "is_revealed" => $is_revealed,
            "credit_cost" => $credit_cost,
            "facebook" => ($is_revealed == true) ? $channel->facebook : null,
            "twitter" => ($is_revealed == true) ? $channel->twitter : null,
            "instagram" => ($is_revealed == true) ? $channel->instagram : null,
            "channel_email" => $channel->channel_email,
            "inf_recommend" => ($is_revealed == true ) ? $channel->inf_recommend : null,
            "onehit" => ($is_revealed == true ) ? $channel->onehit : null,
            "oldcontent" => ($is_revealed == true ) ? $channel->oldcontent : null,
            "fair_price" => ($is_revealed == true ) ? $channel->fair_price : null,
            "number" => $channel->number,
            "is_email" => (!empty($channel->channel_email)) ? true : false,
            "is_number" => (!empty($channel->number)) ? true : false,
        ];
    }
    return $channel_list;
}


function instagramData($channelList,$name) {
    $user = getUser();
    $channel_list =[];
    foreach ($channelList as $key => $channel) {
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

        $channel_list[] = [
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
            "channel_email" => $channel->channel_email,
            "inf_score" => ($is_revealed == true ) ? $channel->inf_score : null,
            "website" => ($is_revealed == true ) ? $channel->website : null,
            "inf_price" => ($is_revealed == true ) ? $channel->inf_price : null,
            "pitching_price" => ($is_revealed == true ) ? $channel->pitching_price : null,
            "story_price" => ($is_revealed == true ) ? $channel->story_price : null,
            "fair_price" => ($is_revealed == true ) ? $channel->fair_price : null,
            "number" => $channel->phone,
            "is_email" => (!empty($channel->emails)) ? true : false,
            "is_number" => (!empty($channel->phone)) ? true : false,
        ];

        
    }
    return $channel_list;
}





