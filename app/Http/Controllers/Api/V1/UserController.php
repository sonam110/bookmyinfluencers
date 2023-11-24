<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Channel;
use App\Models\Invitation;
use App\Models\AppliedCampaign;
use App\Models\Order;
use App\Models\Campaign;
use App\Models\ChannelSearchData;
use App\Models\ChannelEngagement;
use App\Models\ChannelUpdate;
use App\Models\ChannelViews;
use App\Models\TransactionHistory;
use App\Models\Message;
use App\Models\Favourite;
use App\Models\Revealed;
use App\Models\Subscriptions;
use App\Models\SubscriptionPlan;
use App\Models\ChannelAssociation;
use App\Models\CreateList;
use App\Models\OauthAccessTokens;
use App\Models\websiteSetting;
use Validator;
use Auth;
use Exception;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Mail\SendResetPassworkLink;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Alaouy\Youtube\Facades\Youtube;
use App\Rules\YoutubeRule;
use Mail;
use App\Mail\EmailVerificationMail;
use App\Mail\WelcomeMail;
use App\Mail\ForgotPasswordMail;

class UserController extends Controller {

    //===============Login Api//

    public function login(Request $request) {
        // \DB::commit();
       // print_r(USD2INR('1'));
       // die;
        $validator = Validator::make($request->all(), [
                    $this->username() => 'required|string',
                    'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!empty($user)) {

                if (Hash::check($request->password, $user->password)) {
                    if ($user->status == '0') {
                        return prepareResult(false, 'Your account is  deactivate please contact to your admin', [], $this->unauthorized);
                    }

                    if ($user->status == '2') {
                        return prepareResult(false, 'Your account is permanently deactivate please contact to your admin', [], $this->unauthorized);
                    }

                    if ($this->attemptLogin($request)) {
                        if ($user->status == '1') {

                            $token = auth()->user()->createToken('authToken')->accessToken;

                            if (empty($token)) {
                                return prepareResult(false, 'Unable to generate token', [], $this->unprocessableEntity);
                            } else {

                                $user = User::where('id', $user->id)->first();
                                $user['access_token'] = $token;
                                $user['user_type'] = @Auth::user()->roles[0]->name;
                                $user['reg_status'] = true;
                                return prepareResult(true, "User Logged in successfully", $user, $this->success);
                            }
                        } else {
                            $user = User::where('id', $user->id)->first();
                            $token = auth()->user()->createToken('authToken')->accessToken;
                            $user['access_token'] = $token;
                            $user['reg_status'] = false;
                            return prepareResult(true, "Please complete your Registration", $user, $this->success);
                        }
                    }
                } else {
                    return prepareResult(false, 'Wrong Password', [], $this->unprocessableEntity);
                }
            } else {
                return prepareResult(false, 'Unable to find user', [], $this->bad_request);
            }
        } catch (Exception $exception) {
            print_r($exception->getMessage());
            die;
            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function validateLogin(Request $request) {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    public function username() {
        return 'email';
    }

    protected function sendLoginResponse(Request $request) {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson() ? new Response('', 204) : redirect()->intended($this->redirectPath());
    }

    protected function attemptLogin(Request $request) {
        return $this->guard()->attempt(
                        $this->credentials($request), $request->filled('remember')
        );
    }

    protected function guard() {
        return Auth::guard();
    }

    protected function credentials(Request $request) {
        return $request->only($this->username(), 'password');
    }

    //Foget Password//

    public function forgetPassword(Request $request) {
        $validator = \Validator::make($request->all(),[ 
            'email'     => 'required|email'
        ]);

        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }

        try {
            $user = User::where('email',$request->email)->first();
            if (!$user) {
               return prepareResult(false, 'Unable to find user', [], $this->not_found);
            }

            if(in_array($user->status, [0,2,3])) {
                 return prepareResult(false, 'Your account is inactive please contact to admin.', [], $this->unauthorized);
            }
            if ($user->is_google=='1') {
               return prepareResult(false, 'You are not authorize to do this action', [], $this->unauthorized);
            }
            //Delete if entry exists
            DB::table('password_resets')->where('email', $request->email)->delete();

            $token = \Str::random(64);
            DB::table('password_resets')->insert([
              'email' => $request->email, 
              'token' => $token, 
              'created_at' => Carbon::now()
            ]);

            $baseRedirURL = env('APP_URL');
            $content = [
                "name" => $user->fullname,
                "passowrd_link" => $baseRedirURL.'/reset-password/'.$token
            ];

            if (env('IS_MAIL_ENABLE', true) == true) {
               
                $recevier = Mail::to($request->email)->send(new ForgotPasswordMail($content));
            }
            return prepareResult(true, 'Password Reset link has been sent to your registered email id',$request->email, $this->success);

        } catch (\Throwable $e) {
            \Log::error($e);
            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }


    }
     public function updatePassword(Request $request)
    {
        $validator = \Validator::make($request->all(),[ 
            'password'  => 'required|string|min:8',
            'token'     => 'required'
        ]);

        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }
        try {
            $tokenExist = DB::table('password_resets')
                ->where('token', $request->token)
                ->first();
            if (!$tokenExist) {
                return prepareResult(false,'Token Expired', [], $this->unauthorized);
            }

            $user = User::where('email',$tokenExist->email)->first();
            if (!$user) {
                 return prepareResult(false, 'Unable to find user', [], $this->not_found);
            }

            if(in_array($user->status, [0,2])) {
                return prepareResult(false, 'Your account is inactive please contact to admin.', [], $this->unauthorized);
            }

            $user = User::where('email', $tokenExist->email)
                    ->update(['password' => Hash::make($request->password)]);
 
            DB::table('password_resets')->where(['email'=> $tokenExist->email])->delete();

            return prepareResult(true, "Password Updated Successfully.", [], $this->success);

        } catch (\Throwable $e) {
            \Log::error($e);
            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }

    public function logout(Request $request) {

        $user = getUser();
        if (!is_object($user)) {
            return prepareResult(false, "User Not Found", [], $this->not_found);
        }
        if (Auth::check()) {
            $token = $request->bearerToken();
            Auth::user()->token()->revoke();
            return prepareResult(true, 'Logout Successfully', [], $this->success);
        } else {
            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }

    public function bearerToken() {
        $header = $this->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }

    public function changePassword(Request $request) {
        try {

            $user = auth()->user();
            $validator = Validator::make($request->all(), [
                        'old_password' => ['required'],
                        'new_password' => ['required', 'confirmed', 'min:6', 'max:25'],
                        'new_password_confirmation' => ['required']
            ]);

            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }


            if (Hash::check($request->old_password, $user->password)) {
                $data['password'] = \Hash::make($request->new_password);
                $updatePass = User::updateOrCreate(['id' => $user->id], $data);

                return prepareResult(true, "Password Updated Successfully.", [], $this->success);
            } else {

                return prepareResult(false, 'Incorrect old password, Please try again with correct password', [], $this->unprocessableEntity);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function changeEmail(Request $request) {
        try {

            $user = auth()->user();
            $validator = Validator::make($request->all(), [
                        'current_email' => ['required', 'string', 'email', 'max:255'],
                        'new_email' => ['required', 'string', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }


            $checkEmail = User::where('email', $request->new_email)->where('id', '!=', $user->id)->first();
            if (is_object($checkEmail)) {
                return prepareResult(false, "This Email id is already exist", [], $this->not_found);
            }
            if ($request->current_email == $user->email) {
                $data['email'] = $request->new_email;
                $updateEmail = User::updateOrCreate(['id' => $user->id], $data);
                return prepareResult(true, "Email Updated Successfully.", [], $this->success);
            } else {

                return prepareResult(false, 'Incorrect Email, Please try again with correct password', [], $this->unprocessableEntity);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function getProfile(Request $request) {
        try {
            $user = getUser();
            $prifleDetail = DB::table('users')->where('id', $user->id)
                    ->where('status', '1')
                    ->first();
            return prepareResult(true, "Profile Detail", $prifleDetail, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }

    
    public function dashboard(Request $request) {
        try {
            $user = getUser();
            $data = [];
            $accessTokenDetail = OauthAccessTokens::where('user_id', $user->id)->count();
            if ($user->roles[0]->name == 'brand') {
                $appLicationCount = DB::table('applied_campaigns')->select(DB::raw('COUNT(IF(applied_campaigns.status = "1", 0, NULL)) as campaignCount'),
                    DB::raw('COUNT(IF(applied_campaigns.status = "1", 0, NULL)) as activeProposalsCount'),
                    DB::raw('COUNT(IF(applied_campaigns.status = "1", 0, NULL)) as newProposalCounts'),
                 )->where('brand_id', $user->id)->first();

                $data['campaignCount'] = @$appLicationCount->campaignCount;
                $data['activeProposalsCount'] = @$appLicationCount->activeProposalsCount;
                $data['newProposalCounts'] = @$appLicationCount->newProposalCounts;
                $data['liveCampaignsCount'] = DB::table('campaigns')->where('brand_id', $user->id)->where('status', '1')->count();
                $orderCount = DB::table('orders')->select(DB::raw('COUNT(*) as totalCount'),
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as liveCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as cancelledCount'),
                    DB::raw('COUNT(IF(status = "1", 0, NULL)) as completedCount'),
                 )->where('brand_id', $user->id)->first();

                $data['lifeTimeCampaignsCount'] = $data['liveCampaignsCount'] + @$orderCount->completedCount;
                $data['revealedChannelsCount'] = DB::table('revealeds')->where('user_id', $user->id)->count();
                $data['favouritesChannelCount'] = DB::table('favourites')->where('user_id', $user->id)->count();
                $data['activeOrdersCount'] =  @$orderCount->liveCount;
                $data['lifeTimeOrdersCount'] = @$orderCount->completedCount;
                $data['is_login_first_time'] = $user->is_login_first_time;
                $data['category_preferences'] = (empty($user->category_preferences)) ? false : true;
                $plan = \DB::table('subscriptions')->select('id', 'subscription_plan_id', 'expire_at')->where('user_id', $user->id)->where('status', 1)->first();
                $now = Carbon::now();
                $plan_expire = \DB::table('subscriptions')->select('id', 'subscription_plan_id', 'expire_at')->where('user_id', $user->id)->where('status', 1)->whereDate('expire_at', '<', $now->toDateString())->first();
                $data['plan_id'] = ($plan) ? $plan->subscription_plan_id : null;
                $data['plan_expire'] = (!empty($plan_expire)) ? 1 : 0;
                $data['totalCredit'] = $user->credit_balance;
                $data['totalUsedCredit'] = DB::table('transaction_histories')->where('user_id', $user->id)->where('bal_type', '2')->where('type', '2')->sum('amount');

                $channel_list = [];
                $channellist = DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','blur_image','tag_category','facebook','twitter','instagram','inf_recommend','price_view_ratio')
                        ->where('channel_id', '!=', '')
                        ->where('status', '!=', '2')
                         ->limit(11)->inRandomOrder()->get();
                foreach ($channellist as $key => $channel) {
                    $channel_association = \DB::table('channel_association')->select('id','internal_channel_id','promotion_price','influ_id','is_default','is_verified')->where('internal_channel_id', $channel->id)->first();
                    $vidoe_promotion_price = (!empty($channel_association)) ? $channel_association->promotion_price : 0;
                    if ($channel->credit_cost > 0) {                      
                        $is_favourite = false;
                        $favourite = DB::table('favourites')->select('id')->where('user_id', $user->id)->where('channel_id', $channel->channel_id)->first();
                        if (!empty($favourite)) {
                            $is_favourite = true;
                        }
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
                        $revealed = DB::table('revealeds')->select('id')->where('user_id', $user->id)->where('channel_id', $channel->channel_id)->first();
                        if (!empty($revealed)) {
                            $is_revealed = true;
                        }
                        $is_invite = false;
                        $invite = DB::table('invitations')->select('id')->where('brand_id', $user->id)->where('channel_id', $channel->channel_id)->first();
                        if (!empty($invite)) {
                            $is_invite = true;
                        }

                        $credit_cost = ($channel && isset($channel->credit_cost)) ? $channel->credit_cost : 0;
                        $channel_link = "https://www.youtube.com/channel/".$channel->channel."";
                        $channel_list[] = [
                            "id" => $channel->id,
                            "influ_id" => (!empty($channel_association)) ? $channel_association->influ_id : '2',
                            "channel_id" => $channel->channel_id,
                            "channel" => $channel->channel,
                            "channel_lang" => $channel->channel_lang,
                            "channel_link" => ($is_revealed == true) ? $channel_link : null,
                            "promotion_price" => ($is_revealed == true) ? changePrice($vidoe_promotion_price,$channel->currency) : null,
                            "is_default" => $channel->is_default,
                            "is_verified" => $channel->is_verified,
                            "title" => ($is_revealed == true) ? $title : null,
                            "description" => ($is_revealed == true) ? $description : null,
                            "viewCount" => $viewCount,
                            "subscriberCount" => $subscriberCount,
                            "videoCount" => $videoCount,
                            "ViewsSubs" => $ViewsSubs,
                            "EngagementRate" => $EngagementRate,
                            "EstViews" => $EstViews,
                            "cpm" => ($is_revealed == true) ? $ViewsPrice : null,
                            "price_view_ratio" => ($is_revealed == true) ? $channel->price_view_ratio : null,
                            "currency" => $channel->currency,
                            "cat_percentile_1" => ($is_revealed == true) ? $channel->cat_percentile_1 : null,
                            "cat_percentile_5" => ($is_revealed == true) ? $channel->cat_percentile_5 : null,
                            "cat_percentile_10" => ($is_revealed == true) ? $channel->cat_percentile_10 : null,
                            "profile_pic" => ($is_revealed == true) ? $channel->image_path : null,
                            "channel_email" => ($is_revealed == true) ? $channel->email : null,
                            "facebook" => ($is_revealed == true) ? $channel->facebook : null,
                            "twitter" => ($is_revealed == true) ? $channel->twitter : null,
                            "instagram" => ($is_revealed == true) ? $channel->instagram : null,
                            "inf_recommend" => ($is_revealed == true) ? $channel->inf_recommend : null,
                            "blur_image" => $channel->blur_image,
                            "is_favourite" => $is_favourite,
                            "is_revealed" => $is_revealed,
                            "is_invite" => $is_invite,
                            "credit_cost" => $credit_cost,
                            //"categories" => $channel->categories,
                            "tags" => tags($channel->tags),
                            "tag_category" => $channel->tag_category,
                        ];
                    }
                }
                $data['channel_list'] = $channel_list;
                $data['fullname'] = $user->fullname;
                $data['is_campaign_create'] = ($data['liveCampaignsCount'] > 0) ? true : false;
            }
            if ($user->roles[0]->name == 'influencer') {
                $data = [];
                $camp_ids = DB::table('applied_campaigns')->where('influ_id', $user->id)->pluck('camp_id')->toArray();
                $data['invitationCount'] = DB::table('invitations')->where('influ_id', $user->id)->count();
                $data['campaignCount'] = DB::table('campaigns')->whereNotIn('id', $camp_ids)->where('status', '1')->where('visibility', '1')->count();
                $data['proposalCount'] = DB::table('applied_campaigns')->whereIn('status', ['0', '2'])->where('influ_id', $user->id)->count();

                $data['ordersCount'] = DB::table('orders')->where('influ_id', $user->id)->count();
                $data['unverifiedChannelCound'] = \DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_verified', '0')
                ->where('channels.channel_id', '!=', '')
                ->where('channels.status', '!=', '2')
                ->count();

                $data['is_login_first_time'] = $user->is_login_first_time;
                $data['default_channel'] = \DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_default', '1')->with('infInfo:id,fullname')->first();

                $channel_list = [];
                $channellist = \DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channels.channel_id', '!=', '')
                                ->where('channel_association.influ_id', $user->id)
                                ->where('channels.channel_id', '!=', '')
                                ->where('channels.status', '!=', '2')
                                ->orderBy('channel_association.is_default', 'DESC')
                                ->limit(5)->get();
                foreach ($channellist as $key => $channel) {
                    $title = ($channel && isset($channel->channel_name)) ? $channel->channel_name : '';
                    $description = $channel && isset($channel->yt_description) ? $channel->yt_description : '';
                    $viewCount = $channel && isset($channel->views) ? $channel->views : '';
                    $subscriberCount = $channel && isset($channel->subscribers) ? $channel->subscribers : '';
                    $videoCount = $channel && isset($channel->videos) ? $channel->videos : '';

                    $ViewsSubs = $channel && isset($channel->views_sub_ratio) ? $channel->views_sub_ratio : '';
                    $EngagementRate = $channel && isset($channel->engagementrate) ? $channel->engagementrate . '%' : '';

                    $EstViews = ($channel) ? $channel->exposure : '0';

                    $price = ($EstViews != 0) ? $channel->promotion_price / $EstViews : 0;
                    $ViewsPrice = round($price * 1000, 2);
                    $channel_link = "https://www.youtube.com/channel/".$channel->channel."";
                    $channel_list[] = [
                        "id" => $channel->id,
                        "influ_id" => $channel->influ_id,
                        "channel_id" => $channel->channel_id,
                        "canonical_name" => $channel->canonical_name,
                        "channel" => $channel->channel,
                        "channel_link" => $channel_link,
                        "promotion_price" => $channel->promotion_price,
                        "is_default" => $channel->is_default,
                        "is_verified" => $channel->is_verified,
                        "title" => $title,
                        "description" => $description,
                        "viewCount" => $viewCount,
                        "subscriberCount" => $subscriberCount,
                        "videoCount" => $videoCount,
                        "ViewsSubs" => $ViewsSubs,
                        "EngagementRate" => $EngagementRate,
                        "EstViews" => $EstViews,
                        "cpm" => $ViewsPrice,
                        "price_view_ratio" => $channel->price_view_ratio,
                        "currency" => $channel->currency,
                        "cat_percentile_1" => $channel->cat_percentile_1,
                        "cat_percentile_5" => $channel->cat_percentile_5,
                        "cat_percentile_10" => $channel->cat_percentile_10,
                        "channel_email" => $channel->email,
                        "profile_pic" => $channel->image_path,
                        "blur_image" => $channel->blur_image,
                    ];
                }
                $data['channel_list'] = $channel_list;
                $data['fullname'] = $user->fullname;
                $data['brands'] = AppliedCampaign::select('id', 'camp_id', 'influ_id', 'channel_id', 'brand_id')->with('campInfo')->where('influ_id', $user->id)->where('status', '1')->groupby('brand_id')->limit(5)->get();
            }

            return prepareResult(true, 'Dashboard', $data, $this->success);
        } catch (Exception $exception) {
            \Log::info($exception->getMessage());
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }
    public function userDashboard(Request $request) {
        try {
            $user = getUser();
            $data = [];
            $accessTokenDetail = OauthAccessTokens::where('user_id', $user->id)->get()->count();
            if ($user->roles[0]->name == 'brand') {
                $appLicationCount = DB::table('applied_campaigns')->select(DB::raw('COUNT(IF(applied_campaigns.status = "1", 0, NULL)) as campaignCount'),
                    DB::raw('COUNT(IF(applied_campaigns.status = "0", 0, NULL)) as activeProposalsCount'),
                    DB::raw('COUNT(IF(applied_campaigns.status = "1", 0, NULL)) as newProposalCounts'),
                 )->where('brand_id', $user->id)->first();

                $data['campaignCount'] = @$appLicationCount->campaignCount;
                $data['activeProposalsCount'] = @$appLicationCount->activeProposalsCount;
                $data['newProposalCounts'] = DB::table('applied_campaigns')->whereIn('status',['0','2','3'])->where('brand_id', $user->id)->get()->count();
                $data['liveCampaignsCount'] = DB::table('campaigns')->where('brand_id', $user->id)->where('status', '1')->get()->count();
                $orderCount = DB::table('orders')->select(DB::raw('COUNT(*) as totalCount'),
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as liveCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as cancelledCount'),
                    DB::raw('COUNT(IF(status = "1", 0, NULL)) as completedCount'),
                 )->where('brand_id', $user->id)->where('orders.status','!=','3')->first();

                $data['lifeTimeCampaignsCount'] = $data['liveCampaignsCount'] + @$orderCount->completedCount;
                $data['revealedChannelsCount'] = DB::table('revealeds')->where('user_id', $user->id)->get()->count();
                $data['favouritesChannelCount'] = DB::table('favourites')->where('user_id', $user->id)->get()->count();
                $data['activeOrdersCount'] =  @$orderCount->liveCount;
                $data['lifeTimeOrdersCount'] = @$orderCount->completedCount;
                $data['is_login_first_time'] = $user->is_login_first_time;
                $data['category_preferences'] = (empty($user->category_preferences)) ? false : true;
                $plan = \DB::table('subscriptions')->select('id', 'subscription_plan_id', 'expire_at')->where('user_id', $user->id)->where('status', 1)->first();
                $now = Carbon::now();
                $plan_expire = \DB::table('subscriptions')->select('id', 'subscription_plan_id', 'expire_at')->where('user_id', $user->id)->where('status', 1)->whereDate('expire_at', '<', $now->toDateString())->first();
                $data['plan_id'] = ($plan) ? $plan->subscription_plan_id : null;
                $data['plan_expire'] = (!empty($plan_expire)) ? 1 : 0;
                $data['totalCredit'] = $user->credit_balance;
                $data['totalUsedCredit'] = DB::table('transaction_histories')->where('user_id', $user->id)->where('bal_type', '2')->where('type', '2')->sum('amount');

            
                $data['fullname'] = $user->fullname;
                $data['is_campaign_create'] = ($data['liveCampaignsCount'] > 0) ? true : false;
            }
            if ($user->roles[0]->name == 'influencer') {
                $data = [];
                $camp_ids = DB::table('applied_campaigns')->where('influ_id', $user->id)->pluck('camp_id')->toArray();
                $data['invitationCount'] = DB::table('invitations')->where('influ_id', $user->id)->get()->count();
                $data['campaignCount'] = DB::table('campaigns')->whereNotIn('id', $camp_ids)->where('status', '1')->where('visibility', '1')->count();
                $data['proposalCount'] = DB::table('applied_campaigns')->whereIn('status', ['0', '2', '3'])->where('influ_id', $user->id)->count();

                $data['ordersCount'] = DB::table('orders')->where('influ_id', $user->id)->where('status','0')->where('orders.status','!=','3')->count();
                $data['unverifiedChannelCound'] = \DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_verified', '0')
                ->where('channels.channel_id', '!=', '')
                ->where('channels.status', '!=', '2')
                ->count();

                $data['is_login_first_time'] = $user->is_login_first_time;
                $data['default_channel'] = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_default', '1')->first();

                $channel_list = [];
                $channellist = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channels.channel_id', '!=', '')
                                ->where('channel_association.influ_id', $user->id)
                                ->where('channels.channel_id', '!=', '')
                                ->where('channels.status', '!=', '2')
                                ->orderBy('channel_association.is_default', 'DESC')
                                ->limit(5)->get();
                foreach ($channellist as $key => $channel) {
                    $title = ($channel && isset($channel->channel_name)) ? $channel->channel_name : '';
                    $description = $channel && isset($channel->yt_description) ? $channel->yt_description : '';
                    $viewCount = $channel && isset($channel->views) ? $channel->views : '';
                    $subscriberCount = $channel && isset($channel->subscribers) ? $channel->subscribers : '';
                    $videoCount = $channel && isset($channel->videos) ? $channel->videos : '';

                    $ViewsSubs = $channel && isset($channel->views_sub_ratio) ? $channel->views_sub_ratio : '';
                    $EngagementRate = $channel && isset($channel->engagementrate) ? $channel->engagementrate . '%' : '';

                    $EstViews = ($channel) ? $channel->exposure : '0';

                    $price = ($EstViews != 0) ? $channel->promotion_price / $EstViews : 0;
                    $ViewsPrice = round($price * 1000, 2);

                    $channel_list[] = [
                        "id" => $channel->id,
                        "influ_id" => $channel->influ_id,
                        "channel_id" => $channel->channel_id,
                        "canonical_name" => $channel->canonical_name,
                        "channel" => $channel->channel,
                        "channel_link" => $channel->channel_link,
                        "promotion_price" => $channel->promotion_price,
                        "is_default" => $channel->is_default,
                        "is_verified" => $channel->is_verified,
                        "title" => $title,
                        "description" => $description,
                        "viewCount" => $viewCount,
                        "subscriberCount" => $subscriberCount,
                        "videoCount" => $videoCount,
                        "ViewsSubs" => $ViewsSubs,
                        "EngagementRate" => $EngagementRate,
                        "EstViews" => $EstViews,
                        "cpm" => $ViewsPrice,
                        "price_view_ratio" => $channel->price_view_ratio,
                        "currency" => $channel->currency,
                        "cat_percentile_1" => $channel->cat_percentile_1,
                        "cat_percentile_5" => $channel->cat_percentile_5,
                        "cat_percentile_10" => $channel->cat_percentile_10,
                        "channel_email" => $channel->email,
                        "profile_pic" => $channel->image_path,
                        "blur_image" => $channel->blur_image,
                    ];
                }
                $data['channel_list'] = $channel_list;
                $data['fullname'] = $user->fullname;
                //$data['brands'] = AppliedCampaign::select('id', 'camp_id', 'influ_id', 'channel_id', 'brand_id')->with('campInfo')->where('influ_id', $user->id)->where('status', '1')->groupby('brand_id')->limit(5)->get();
            }

            return prepareResult(true, 'Dashboard', $data, $this->success);
        } catch (Exception $exception) {
            \Log::info($exception->getMessage());
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    // function for check email exist or not
    public function CheckEmail(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                        'email' => 'required|email',
                            ],
                            [
                                'email.required' => 'email is required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $checkuser = DB::table('users')
                    ->where('email', $request->email)
                    ->first();
            if (!empty($checkuser)) {
                return prepareResult(true, 'Email exists', $checkuser, $this->success);
            } else {
                return prepareResult(false, 'Email not exists', [], $this->bad_request);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }

    // function for email verification
    public function EmailVerification(Request $request) {
        
    }

// function for registration
    public function registration(Request $request) {
        try {
            if ($request->usertype == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'name' => 'required',
                            'email' => 'required|email',
                            'password' => 'required',
                            'usertype' => 'required',
                            'phone' => 'required',
                            'channel_url' => ['required'],
                            'language' => 'required',
                            'currency' => 'required',
                            'promotion_price' => 'required',
                                ],
                                [
                                    'name.required' => 'name is required',
                                    'email.required' => 'email is required',
                                    'password.required' => 'password is required',
                                    'usertype.required' => 'usertype is required',
                                    'phone.required' => 'phone is required',
                                    'channel_url.required' => 'channel_url is required',
                                    'language.required' => 'language is required',
                                    'currency.required' => 'currency is required',
                                    'promotion_price.required' => 'name is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $checkuser = DB::table('users')
                        ->where('email', $request->email)
                        ->first();

                if (!empty($checkuser)) {
                    return prepareResult(false, 'Email already exists', [], $this->bad_request);
                } else {
                    if (strlen($request->channel_url) == '24') {
                        $channelUrl = "https://www.youtube.com/channel/" . $request->channel_url . "";
                    } else {
                        $channelUrl = "https://www.youtube.com/@" . $request->channel_url . "";
                    }
                    $checkinternal_channel_id = checkYoutubeUrlValid($channelUrl);
                    if (!$checkinternal_channel_id) {
                        return prepareResult(false, 'This is not a valid YouTube channel UR', [], $this->unprocessableEntity);
                    }

                    $channelinfo = ChannelSearchData::select('id', 'channel', 'image_path', 'status', 'channel_name', 'tag_category')->where('channel', $checkinternal_channel_id)->first();

                    $image = ($channelinfo) ? $channelinfo->image_path : null;
                    $blur_image = null;
                    $destinationPath = 'uploads/channel/';
                    if ($image) {
                        $file = $image;
                        $prImage = time() . '-blur-img.' . 'jpg';
                        $img = \Image::make($file);
                        $resource = $img->blur(50)->stream()->detach();
                        $storagePath = Storage::disk('s3')->put('channel/blur_img/' . $prImage, $resource, 'public');
                        $blur_image = Storage::disk('s3')->url('channel/blur_img/' . $prImage);
                    }

                    if (!empty($channelinfo)) {
                        $checkAlready = Channel::where('channel_id', $channelinfo->id)->first();
                        if (!empty($checkAlready)) {
                            return prepareResult(false, "channel already exist", [], $this->bad_request);
                        } else {
                            $user = User::create([
                                        'fullname' => $request->name,
                                        'email' => $request->email,
                                        'password' => Hash::make($request->password),
                                        'userType' => $request->usertype,
                                        'phone' => $request->phone,
                                        'currency' => $request->currency,
                                        'profile_photo' => $image,
                                        'language' => $request->language,
                                        'is_google' => 0,
                            ]);

                            $user->status = 1;
                            $user->save();

                            $channel = new Channel;
                            $channel->influ_id = $user->id;
                            $channel->plateform = 'youtube';
                            $channel->channel_id = $channelinfo->id;
                            $channel->channel = $checkinternal_channel_id;
                            $channel->channel_link = $request->channel_url;
                            $channel->channel_lang = $request->language;
                            $channel->promotion_price = $request->promotion_price;
                            $channel->is_default = '1';
                            $channel->is_verified = '0';
                            ($channelinfo) ? $channelinfo->status : '1';
                            $channel->channel_name = ($channelinfo) ? $channelinfo->channel_name : null;
                            $channel->tags = ($channelinfo) ? $channelinfo->top_two_tags : null;
                            $channel->tag_category = ($channelinfo) ? $channelinfo->tag_category : null;
                            $channel->image = $image;
                            $channel->blur_image = $blur_image;
                            $channel->status = ($channelinfo) ? '1' : '0';
                            $channel->save();

                            $content = [
                                'name' => $user->fullname,
                            ];
                            Mail::to($user->email)->send(new WelcomeMail($content));
                        }
                    } else {
                        $user = User::create([
                                    'fullname' => $request->name,
                                    'email' => $request->email,
                                    'password' => Hash::make($request->password),
                                    'userType' => $request->usertype,
                                    'phone' => $request->phone,
                                    'currency' => $request->currency,
                                    'profile_photo' => $image,
                                    'language' => $request->language,
                                    'is_google' => 0,
                        ]);

                        $user->status = 1;
                        $user->save();

                        $addChannel = new ChannelSearchData;
                        $addChannel->channel = $checkinternal_channel_id;
                        $addChannel->confirmed = '0';
                        $addChannel->save();

                        $id = ($channelinfo) ? $channelinfo->id : $addChannel->id;

                        $channel = new Channel;
                        $channel->influ_id = $user->id;
                        $channel->plateform = 'youtube';
                        $channel->channel_id = $id;
                        $channel->channel = $checkinternal_channel_id;
                        $channel->channel_link = $request->channel_url;
                        $channel->channel_lang = $request->language;
                        $channel->promotion_price = $request->promotion_price;
                        $channel->is_default = '1';
                        $channel->is_verified = ($channelinfo) ? $channelinfo->status : '0';
                        $channel->channel_name = ($channelinfo) ? $channelinfo->channel_name : null;
                        $channel->tags = ($channelinfo) ? $channelinfo->top_two_tags : null;
                        $channel->tag_category = ($channelinfo) ? $channelinfo->tag_category : null;
                        $channel->image = $image;
                        $channel->blur_image = $blur_image;
                        $channel->status = ($channelinfo) ? '1' : '0';
                        $channel->save();

                        $content = [
                            'name' => $user->fullname,
                        ];
                        Mail::to($user->email)->send(new WelcomeMail($content));
                    }
                }

                $user->assignRole('influencer');
                $token = $user->createToken('authToken')->accessToken;
                $user['access_token'] = $token;
                return prepareResult(true, 'Successfully created', $user, $this->success);
            } else {
                $validator = Validator::make($request->all(), [
                            'name' => 'required',
                            'email' => 'required|email',
                            'password' => 'required',
                            'usertype' => 'required',
                            'phone' => 'required',
                            'currency' => 'required',
                                ],
                                [
                                    'name.required' => 'name is required',
                                    'email.required' => 'email is required',
                                    'password.required' => 'password is required',
                                    'usertype.required' => 'usertype is required',
                                    'phone.required' => 'phone is required',
                                    'currency.required' => 'currency is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $checkuser = DB::table('users')
                        ->where('email', $request->email)
                        ->first();

                if (!empty($checkuser)) {
                    return prepareResult(false, 'Email already exists', [], $this->bad_request);
                } else {
                    $user = User::create([
                                'fullname' => $request->name,
                                'email' => $request->email,
                                'password' => Hash::make($request->password),
                                'userType' => $request->usertype,
                                'plan' => $request->plan,
                                'phone' => $request->phone,
                                'currency' => $request->currency,
                                'is_google' => 0,
                    ]);

                    $user->status = 1;
                    $user->save();
                    $content = [
                        'name' => $user->fullname,
                    ];
                    Mail::to($user->email)->send(new WelcomeMail($content));
                }
                $user->assignRole('brand');
                $token = $user->createToken('authToken')->accessToken;
                $user['access_token'] = $token;
                return prepareResult(true, 'Successfully created', $user, $this->success);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }

    public function emailVerify($id) {
        $user = DB::table('users')->where('id', $id)->first();
        if ($user->status == '0' && $user->email_verified_at == '') {
            $update = DB::table('users')
                    ->where('id', $id)
                    ->update(['status' => '1', 'email_verified_at' => Now()]);

            $content = [
                'name' => $user->fullname,
            ];
            if (env('IS_MAIL_ENABLE', true) == true) {
                Mail::to($user->email)->send(new WelcomeMail($content));
            }
            return prepareResult(true, 'Successfully email verified', $user, $this->success);
        } else {
            return prepareResult(false, 'Email already verified', [], $this->bad_request);
        }
    }

    public function menuCount() {

        try {

            $user = getUser();
            $data = [];
            if ($user->roles[0]->name == 'influencer') {
                $camp_ids = DB::table('applied_campaigns')->where('influ_id', $user->id)->pluck('camp_id')->toArray();
                $inviteCamp_ids = \DB::table('invitations')->where('influ_id', $user->id)->pluck('camp_id')->toArray();
                $data['invitation'] = \DB::table('campaigns')->orderby('id', 'DESC')->whereIn('id', $inviteCamp_ids)->whereNotIn('status', ['5'])->get()->count();
                $data['campaign'] = \DB::table('campaigns')->whereNotIn('id', $camp_ids)->where('visibility', '1')->where('status', '1')->get()->count() + $data['invitation'];
                $data['applicationsCount'] = \DB::table('applied_campaigns')->whereIn('status', ['0', '2','3'])->where('influ_id', $user->id)->get()->count();
                $data['orderCount'] = \DB::table('orders')->where('influ_id', $user->id)->where('orders.status','!=','3')->get()->count();
                $data['notificationsCount'] = DB::table('transaction_histories')->where('status', '1')->WhereIn('type', ['5', '6', '7', '8'])->where('user_id', $user->id)->groupBy('id')->get()->count();
                $data['InboxCount'] = DB::table('messages')->where('receiver_id', $user->id)->where('is_read', '0')->get()->count();
                $data['user_type'] = @Auth::user()->roles[0]->name;
                $channel_url = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_default', '1')->first();
                $data['default_channel'] = ($channel_url) ? $channel_url->canonical_name : null;
                $data['InboxCount'] = Message::where('receiver_id', $user->id)->where('is_read', '0')->get()->count();
               
                
            }
            if ($user->roles[0]->name == 'brand') {
                $data['campaign'] = \DB::table('campaigns')->where('brand_id', $user->id)->get()->count();
                $data['applicationsCount'] = \DB::table('applied_campaigns')->where('brand_id', $user->id)->get()->count();
                $data['orderCount'] = \DB::table('orders')->where('brand_id', $user->id)->where('orders.status','!=','3')->get()->count();
                $data['notificationsCount'] = DB::table('transaction_histories')->where('status', '1')->WhereIn('type', ['5', '6', '7', '8'])->where('user_id', $user->id)->groupBy('id')->get()->count();
                $data['revealedChannels'] = \DB::table('revealeds')->where('user_id', $user->id)->get()->count();
                $data['savedChannelsCount'] =\DB::table('favourites')->where('user_id', $user->id)->where('status', 1)->get()->count();
                $data['InboxCount'] = Message::where('receiver_id', $user->id)->where('is_read', '0')->get()->count();
                $data['user_type'] = @Auth::user()->roles[0]->name;
                $plan = \DB::table('subscriptions')->where('subscriptions.user_id', $user->id)->where('subscriptions.status', 1)->join('subscription_plan','subscriptions.subscription_plan_id','subscription_plan.id')->first();
                $now = Carbon::now();
                $plan_expire = \DB::table('subscriptions')->select('id', 'subscription_plan_id', 'expire_at')->where('user_id', $user->id)->where('status', 1)->whereDate('expire_at', '<', $now->toDateString())->first();
                $data['plan_id'] = ($plan) ? $plan->subscription_plan_id : null;
                $data['plan_expire'] = (!empty($plan_expire)) ? 1 : 0;
                $data['totalCredit'] = $user->credit_balance;
                $data['totalWallet'] = $user->wallet_balance;
                $data['plan'] = $plan;
                $data['totalUsedCredit'] = DB::table('transaction_histories')->where('user_id', $user->id)->where('bal_type', '2')->where('type', '2')->sum('amount');
                $data['totalListCount'] = CreateList::where('brand_id', $user->id)->get()->count();
            }

            $data['websiteSetting'] = websiteSetting::select('id','tax_per')->first();
            $data['uuid'] = $user->uuid;
            $data['last_topup_date'] =  (!empty($user->last_topup_date)) ? date('Y-m-d',strtotime($user->last_topup_date)) : NULL ;
            return prepareResult(true, 'Menu Count', $data, $this->success);
        } catch (Exception $exception) {


            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    /*------get user info-----------------------*/
    // function for check email exist or not
    public function getUserInfo(Request $request) {
        try {
            $checkuser = User::where('email', $request->email)->with('allChannels','RelationshipManager')->first();
            if (!empty($checkuser)) {
                return prepareResult(true, 'user Detail', $checkuser->makeHidden('phone'), $this->success);
            } else {
                //return prepareResult(false, 'Email not exists', [], $this->bad_request);
            }
        } catch (Exception $exception) {
           //print_r($exception->getMessage());
           // die;
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
