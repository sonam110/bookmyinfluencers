<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Channel;
use App\Models\ChannelSearchData;
use App\Models\ChannelEngagement;
use App\Models\InfluncerChannelSyn;
use Validator;
use Auth;
use Exception;
use Illuminate\Support\Facades\Hash;
use Alaouy\Youtube\Facades\Youtube;
use Mail;
use App\Mail\EmailVerificationMail;
use App\Mail\WelcomeMail;
use App\Mail\ChannelMail;
use App\Http\Controllers\Api\V1\ChannelController;
use App\Models\ChannelAssociation;
use App\Http\Controllers\Api\V2\socialiteController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class UserController extends Controller {

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
                    'email' => 'required|email',
                    'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }
        try {
            /*-----------Account Login-------------------------------------*/

            $checkUser = \DB::table('users')->where('email', $request->email)->first();
            if (!$checkUser)  {
                return prepareResult(false, 'User not found', [], $this->not_found);
            }

            if(in_array($checkUser->status, [2])) {
                 return prepareResult(false, 'Account is currently inactive stage. Please contact to Admin', [], $this->unprocessableEntity);
            }
            // if(in_array($checkUser->status, [3])) {
            //     return prepareResult(false, 'Your email verfication is pending please verify your mail. ', [], $this->unprocessableEntity);
            // }

            if (Auth::guard()->attempt($request->only('email', 'password'), $request->filled('remember'))) {
                $user = auth()->user();
                $user['access_token'] = auth()->user()->createToken('authToken')->accessToken;
                $message = '';
                if ($user->status == '1') {
                    if($user->account_type =='0'){
                        $user->account_type = '1';
                        $user->save();
                    }
                    $user['user_type'] = @Auth::user()->roles[0]->name;
                    $user['reg_status'] = true;
                    if($user->userType=="influencer"){
                        $influncerChannelSyn = new InfluncerChannelSyn;
                        $influncerChannelSyn->influ_id = $user->id;
                        $influncerChannelSyn->save();
                        
                    }
                    if($user->currency=='INR'){
                        $from = 'USD';
                        $to = 'INR';
                    } else{
                        $from = 'INR';
                        $to = 'USD';
                    }
                    if($user->manager_id==''){
                        $Subs = \DB::table('subscriptions')->where('user_id',$user->id)->where('status','1')->first();
                        $plan_id =(!empty($Subs)) ? $Subs->subscription_plan_id :'1';

                        updateRelationshipMgr($plan_id,$user->id);
                    }
                    $amount = currencyConvert($from,$to,'1');
                    $saveUser = User::find($user->id);
                    $saveUser->ops_currency = $from;
                    $saveUser->ops_currency_rate = $amount;
                    $saveUser->save();

                    $message = 'User Logged in successfully';
                
                }else {
                    $user['reg_status'] = false;
                    if($user->status == '3'){
                        $message = 'Your email verfication is pending';
                    } else{
                        $message = 'Please complete your Registration';
                    }
                    
                }
                return prepareResult(true, $message, $user, $this->success);
            } else {
                return prepareResult(false, 'Email or Password is wrong', [], $this->unprocessableEntity);
            }
        } catch (Exception $exception) {
//            dd($exception);
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function registration(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
                ]);
        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }
        $checkPrivateAcc = \DB::table('users')->where('email',$request->email)->where('account_type','0')->first();
        if(empty($checkPrivateAcc)){
            $validator = Validator::make($request->all(), [
                        'email' => 'required|email|unique:users',
            ],
            [
                    'email.unique' => 'There is already an account at this email address',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }

        }
        try {
            if(empty($checkPrivateAcc)){
                $user = new User();
            } else{
                $user =  User::find($checkPrivateAcc->id);
            }
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->is_google = 0;
            $user->google_id = null;
            $user->status = 3;
            $user->account_type = '1';
            $user->ip_address = $request->ip();
            $user->email_verified_token = \Str::random(36);
            $user->save();
            $user['reg_status'] = false;
            $user['channel_data'] = socialiteController::fetchChannelByEmail($user->email);
            $baseRedirURL = (!empty(env('APP_URL'))) ? env('APP_URL') :'https://stage.bookmyinfluencers.com';
            $content = [
                'name' => 'User',
                'verify_link' => $baseRedirURL .'/email-verification/' .$user->id . '/' . $user->email_verified_token
            ];
            if (env('IS_MAIL_ENABLE', true) == true) {
                Mail::to($user->email)->send(new EmailVerificationMail($content));
                
            }
            return prepareResult(true, 'User Signed Up successfully ! Verification link has been sent to your registered email id.', $user, $this->success);
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function registrationPhaseTwo(Request $request) {
        try {
            $UserToUpdate = Auth::user();
            
            if ($UserToUpdate->status > 0) {
                $UserToUpdate['reg_status'] = true;
                return prepareResult(true, 'User Is Already Updated', $UserToUpdate, $this->success);
            }
            $rules = ['usertype' => 'required', 'name' => 'required|alpha_spaces',  'currency' => 'required'];
            if (isset($request->usertype) && $request->usertype == 'influencer') {
                //$rules['channel_url'] = 'required';
                //$rules['promotion_price'] = 'required';
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }

            $checkRole = Role::where('name',$request->usertype)->first();
            if(empty($checkRole)){
                return prepareResult(false, 'This role is not found in your system please contact to admin', [], $this->unprocessableEntity);
            }
            $deleteModelRole= \DB::table('model_has_roles')->where('role_id',$checkRole->id)->where('model_id',$UserToUpdate->id)->delete();


            if($request->currency=='INR'){
                $from = 'INR';
                $to = 'USD';
            } else{
                $from = 'USD';
                $to = 'INR';
            }
            if($UserToUpdate->manager_id==''){
                $Subs = \DB::table('subscriptions')->where('user_id',$UserToUpdate->id)->where('status','1')->first();
                $plan_id =(!empty($Subs)) ? $Subs->subscription_plan_id :'1';

                updateRelationshipMgr($plan_id,$UserToUpdate->id);
            }
            $amount = currencyConvert($from,$to,'1');
        

            $UserToUpdate->userType = $request->usertype;
            $UserToUpdate->fullname = $request->name;
            $UserToUpdate->phone = $request->phone;
            $UserToUpdate->language = $request->language;
            $UserToUpdate->currency = $request->currency;
            $UserToUpdate->skype = $request->skype;
            $UserToUpdate->ops_currency = $to;
            $UserToUpdate->ops_currency_rate = $amount;
            $UserToUpdate->detail_in_exchange = ($request->detail_in_exchange== true) ? 1: 0;
            $UserToUpdate->whats_app_notification = ($request->whats_app_notification==true) ? 1 : 0;

            if ($request->usertype == 'influencer' && !empty($request->channel_url)) {
                $channelUrl = "https://www.youtube.com/@" . $request->channel_url . "";
                $checkChannelId = checkYoutubeUrlValid($channelUrl);
             
                if (!$checkChannelId) {
                    return prepareResult(false, 'This is not a valid YouTube channel URL', [], $this->unprocessableEntity);
                }
                $bmiPoolChannel = ChannelSearchData::where('channel', $checkChannelId)->first();
                if (empty($bmiPoolChannel)) {
                    $bmiPoolChannel = new ChannelSearchData;
                    $bmiPoolChannel->credit_cost = '10';
                    $bmiPoolChannel->confirmed = '9';
                }
                $activities = Youtube::getChannelById($checkChannelId);
                $title = ($activities && isset($activities->snippet->title)) ? $activities->snippet->title : '';
                $description = ($activities && isset($activities->snippet->description)) ? $activities->snippet->description : '';
                $viewCount = ($activities && isset($activities->statistics->viewCount)) ? $activities->statistics->viewCount : '0';
                $subscriberCount = ($activities && isset($activities->statistics->subscriberCount)) ? $activities->statistics->subscriberCount : '0';
                $videoCount = ($activities && isset($activities->statistics->videoCount)) ? $activities->statistics->videoCount : '0';
                $profile_pic = ($activities && isset($activities->snippet->thumbnails->high)) ? $activities->snippet->thumbnails->high->url : '';

                $ChannelController = new ChannelController();
                $getVideos = $ChannelController->getLatestFiveVideo($checkChannelId);
                
                $EstViews = round($getVideos['view_count'] / 5, 0);
                $views_sub_ratio = ($subscriberCount != 0 ) ? round(($EstViews / $subscriberCount), 2) : '0';
                $engRate = ($getVideos['view_count'] != 0 ) ? ($getVideos['like_count'] + $getVideos['dislike_count'] + $getVideos['comment_count']) / $getVideos['view_count'] : '0';
                $engagementrate = round(($engRate / 5), 2);

                $bmiPoolChannel->channel = $checkChannelId;
                $bmiPoolChannel->channel_name = $title;
                $bmiPoolChannel->yt_description = $description;
                $bmiPoolChannel->views = $viewCount;
                $bmiPoolChannel->subscribers = $subscriberCount;
                $bmiPoolChannel->videos = $videoCount;
                $bmiPoolChannel->image_path = $profile_pic;
                $bmiPoolChannel->views_sub_ratio = $views_sub_ratio;
                $bmiPoolChannel->engagementrate = $engagementrate;
                $bmiPoolChannel->image_path = $profile_pic;
                $bmiPoolChannel->save();

//                if (empty($bmiPoolChannel)) {
//                    $channelEng = new ChannelEngagement;
//                    $channelEng->channel_id = $checkChannelId;
//                    $channelEng->avgviews = $EstViews;
//                    $channelEng->save();
//                }

                $image = ($bmiPoolChannel) ? $bmiPoolChannel->image_path : null;
                $credit_cost = ($bmiPoolChannel->credit_cost > 0) ? $bmiPoolChannel->credit_cost : '10';
                $blur_image = null;
                if ($image) {
                    $file = $image;
                    $prImage = $bmiPoolChannel->channel . '-blur-img.' . 'jpg';
                    $img = \Image::make($file);
                    $resource = $img->blur(50)->stream()->detach();
                    $storagePath = Storage::disk('s3')->put('channel/blur_img/' . $prImage, $resource, 'public');
                    $blur_image = Storage::disk('s3')->url('channel/blur_img/' . $prImage);
                }
                $UserToUpdate->profile_photo = $image;
                $internalChannel = Channel::where('channel', $checkChannelId)->first();
                if (empty($internalChannel)) {
                    $internalChannel = new Channel;
                }
//                $internalChannel->influ_id = 0;
                $internalChannel->plateform = 'youtube';
                $internalChannel->channel_id = $bmiPoolChannel->id;
                $internalChannel->canonical_name = $request->channel_url;
                $internalChannel->channel = $checkChannelId;
                $internalChannel->channel_link = $channelUrl;
                $internalChannel->channel_lang = $request->language;
                $internalChannel->channel_name = (isset($bmiPoolChannel) && $bmiPoolChannel) ? $bmiPoolChannel->channel_name : null;
                $internalChannel->tags = (isset($bmiPoolChannel) && $bmiPoolChannel) ? $bmiPoolChannel->top_two_tags : null;
                $internalChannel->tag_category = (isset($bmiPoolChannel) && $bmiPoolChannel) ? $bmiPoolChannel->tag_category : null;
                $internalChannel->image = $image;
                $internalChannel->blur_image = $blur_image;
                $internalChannel->public_profile_url = '/youtube/' . $checkChannelId;
                $internalChannel->status = '1';

                $internalChannel->email = $bmiPoolChannel->conemail;

                $internalChannel->yt_description = $bmiPoolChannel->yt_description;
                $internalChannel->views = $bmiPoolChannel->views;
                $internalChannel->subscribers = $bmiPoolChannel->subscribers;
                $internalChannel->videos = $bmiPoolChannel->videos;
                $internalChannel->views_sub_ratio = $bmiPoolChannel->views_sub_ratio;
                $internalChannel->engagementrate = $bmiPoolChannel->engagementrate;
                $internalChannel->price_view_ratio = $bmiPoolChannel->price_view_ratio;
                $internalChannel->currency = $bmiPoolChannel->currency;
                $internalChannel->cat_percentile_1 = $bmiPoolChannel->cat_percentile_1;
                $internalChannel->cat_percentile_5 = $bmiPoolChannel->cat_percentile_5;
                $internalChannel->cat_percentile_10 = $bmiPoolChannel->cat_percentile_10;
                $internalChannel->image_path = $bmiPoolChannel->image_path;
                $internalChannel->credit_cost = $credit_cost;
                $internalChannel->exposure = $bmiPoolChannel->exposure;
                $internalChannel->language = $bmiPoolChannel->language;
//                $internalChannel->category = $bmiPoolChannel->category;
                $internalChannel->tag_category = $bmiPoolChannel->tag_category;

                $internalChannel->save();

                $ChannelAssociation = ChannelAssociation::where('influ_id', $UserToUpdate->id)->where('internal_channel_id', $internalChannel->id)->first();
                if (empty($ChannelAssociation)) {
                    $ChannelAssociation = new ChannelAssociation();
                    $ChannelAssociation->is_verified = 0;
                    $ChannelAssociation->type = 0;
                }
                $ChannelAssociation->influ_id = $UserToUpdate->id;
                $ChannelAssociation->internal_channel_id = $internalChannel->id;
                $ChannelAssociation->promotion_price = $request->promotion_price;
                $ChannelAssociation->is_default = 1;
                $ChannelAssociation->yt_key = md5(random_bytes(8));
                $ChannelAssociation->save();
                $content = [
                    "name" => $UserToUpdate->fullname,
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    Mail::to($UserToUpdate->email)->send(new ChannelMail($content));
                }
            }

            $UserToUpdate->assignRole($request->usertype);
            $UserToUpdate->status = '1';
            $UserToUpdate->email_verified_token = '';
            $UserToUpdate->save();

            /*-----------------FreePlan Added---------------------*/
            createUserFreePlan($UserToUpdate->id);
            $UserToUpdate['access_token'] = $UserToUpdate->createToken('authToken')->accessToken;
            $UserToUpdate['reg_status'] = true;

            $content = [
                'name' => $UserToUpdate->fullname,
               // 'verify_link' => env('APP_URL') . '/email-verification/' . $UserToUpdate->id . '/' . $UserToUpdate->email_verified_token
            ];
            if(!empty($request->channel_url)){
                if (!empty($ChannelAssociation) && ($internalChannel->channel_email == $UserToUpdate->email || $internalChannel->email == $UserToUpdate->email) && $UserToUpdate->is_google) {
                    $ChannelAssociation->is_verified = 1;
                    $ChannelAssociation->type = 1;
                    $ChannelAssociation->save();
                }

            }

            if (env('IS_MAIL_ENABLE', true) == true) {
                Mail::to($UserToUpdate->email)->send(new WelcomeMail($content));
               
            }
            return prepareResult(true, 'User Successfully Updated', $UserToUpdate, $this->success);
        } catch (Exception $exception) {
          
            \Log::info($exception->getMessage());
           
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function emailVerify($id, $hash) {
        $user = DB::table('users')->where('id', $id)->where('email_verified_token', $hash)->first();

        if ($user) {
            $updateUser = User::find($id);
            $updateUser->email_verified_at =  Now();
            $updateUser->status =  '0';
            $updateUser->save();
           
            $internalChannel = ChannelAssociation::join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')
                    ->where('channel_association.influ_id', $id)
                    ->where('channel_association.is_default', '1')
                    ->first();
            if(!empty($internalChannel)){
                $ChannelAssociation = ChannelAssociation::where('influ_id', $id)->where('is_default', '1')->where('internal_channel_id', $internalChannel->id)->first();

                if (!empty($ChannelAssociation) && ($internalChannel->channel_email == $user->email || $internalChannel->email == $user->email)) {
                    $ChannelAssociation->is_verified = 1;
                    $ChannelAssociation->type = 1;
                    $ChannelAssociation->save();
                }

            }

            $token = $updateUser->createToken('authToken')->accessToken;
           
            $updateUser['access_token'] = $token;
            self::attachAllChannelBelongsVerifiedEmail($user->email, $id);
            return prepareResult(true, 'Email successfully verified.',$updateUser, $this->success);
        } else {
            return prepareResult(false, 'Bad Link', [], $this->bad_request);
        }
    }

    public static function attachAllChannelBelongsVerifiedEmail($email, $id) {
        $channels = Channel::where('email', $email)->orWhere('channel_email', $email)->get();
        foreach ($channels as $channel) {
            ChannelAssociation::where('internal_channel_id', $channel->id)
                    ->update(['is_verified' => 0, 'type' => 0]);
            $ChannelAssociation = ChannelAssociation::where('influ_id', $id)
                    ->where('internal_channel_id', $channel->id)
                    ->where('influ_id', $id)
                    ->first();

            if (empty($ChannelAssociation)) {
                $ChannelAssociation = new ChannelAssociation();
            }
            $ChannelAssociation->influ_id = $id;
            $ChannelAssociation->internal_channel_id = $channel->id;
            $ChannelAssociation->is_verified = 1;
            $ChannelAssociation->plateform_type = 1;
            $ChannelAssociation->yt_key = md5(random_bytes(8));
            $ChannelAssociation->type = 1;
            $ChannelAssociation->save();
        }
    }
}
