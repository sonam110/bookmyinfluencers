<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Amirsarhang\Instagram;
use Illuminate\Support\Facades\Http;
use App\Models\Channel;
use App\Models\AppliedCampaign;
use App\Models\ChannelSearchData;
use App\Models\InfluencerChannel;
use App\Models\ChannelAssociation;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderProcess;
use App\Models\Subscriptions;
use App\Models\InstagramData;
use App\Models\PoolInstagramData;
use Validator;
use Auth;
use Exception;
use DB;
use Storage;
class InstagramController extends Controller
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authData;

    public function __construct()
    {
        $this->clientId = config('app.INSTAGRAM_CLIENT_ID');
        $this->clientSecret = config('app.INSTAGRAM_CLIENT_SECRET');
        $this->redirectUri = config('app.INSTAGRAM_REDIRECT_URI');
    }
    ///----------Insta-------------------
    public function auth()
    {
        try {
        // Go to FB Documentations to see available permissions
        $permissions = [
            'public_profile', 
            'instagram_basic',
            'instagram_manage_insights ',
            'pages_show_list',
            'instagram_manage_comments',
            'instagram_manage_messages',
            'pages_manage_engagement',
            'pages_read_engagement',
            'pages_manage_metadata'
        ];
        
        // Generate Instagram Graph Login URL
        $login = (new Instagram())->getLoginUrl($permissions);
        
        // Redirect To Facebook Login & Select Account Page
        return header("Location: ".$login);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    //----------callback----------------------
    public function callback(Request $request)
    {
    
        $ig_data = [];

        $ig_data['client_id'] = $this->clientId; //replace with your facebook app ID

        $ig_data['client_secret'] = $this->clientSecret; //replace with your facebook app secret

        $ig_data['grant_type'] = 'authorization_code';

        $ig_data['redirect_uri'] = $this->redirectUri ; //create this redirect uri in your routes web.php file


        $ig_atu = "https://graph.facebook.com/v13.0/oauth/access_token";
        $ig_data['code'] =  $request->code;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_atu);

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($ig_data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_auth_data = curl_exec($ch);

        curl_close ($ch);

        $ig_auth_data = json_decode($ig_auth_data, true);
        if (!isset($ig_auth_data['error_message'])) {

            $this->authData['access_token'] = $ig_auth_data['access_token'];

        }

        $user = User::find(3);
        // Generate User Access Token After User Callback To Your Site
        //$accessToken =  Instagram::getUserAccessToken();
        $accessToken =  $this->authData['access_token'];
        $instagram = new Instagram($accessToken);

    // Will return all instagram accounts that connected to your facebook selected pages.
        $accounts =  $instagram->getConnectedAccountsList(); 
        
        $accountlist = $accounts['instagramAccounts'];
        return $accountlist;
        foreach ($accountlist as $key => $account) {
            $bmiPoolData = PoolInstagramData::where('username',$account->username)->first();
            $checkAccountAlready = DB::table('channel_association')->join('instagram_data', 'channel_association.internal_channel_id', '=', 'instagram_data.id')->where('channel_association.influ_id', $user->id)->where('instagram_data.username',$account->username)->where('channel_association.plateform_type','2')->where('instagram_data.status', '!=', '5')->first();
            if(empty($checkAccountAlready)){
                $username = $account->username;
                $name = (isset($account->name))? $account->name :  @$bmiPoolData->name;
                $bio = (isset($account->biography))? $account->biography :  @$bmiPoolData->bio;
                
                $followers = (isset($account->followers_count))? $account->followers_count : @$bmiPoolData->followers;
                $following = (isset($account->follows_count))? $account->follows_count : @$bmiPoolData->following;
                $profile_post =  (isset($account->media_count))? $account->media_count : @$bmiPoolData->profile_post;
                $following_profile = (!empty($bmiPoolData)) ? $bmiPoolData->following_profile  : NULL;
                $avg_likes = (!empty($bmiPoolData)) ? $bmiPoolData->avg_likes  : 0;
                $engmnt_rate = (!empty($bmiPoolData)) ? $bmiPoolData->engmnt_rate  : 0;
                $fair_price_status = (!empty($bmiPoolData)) ? $bmiPoolData->fair_price_status  : 1;
                $fair_price = (!empty($bmiPoolData)) ? $bmiPoolData->fair_price  : 0;
                $inf_score_status = (!empty($bmiPoolData)) ? $bmiPoolData->inf_score_status  : 1;
                $inf_score = (!empty($bmiPoolData)) ? $bmiPoolData->inf_score  : 1;
                $website = (!empty($bmiPoolData)) ? $bmiPoolData->website  : NULL;
                $emails = (!empty($bmiPoolData)) ? $bmiPoolData->emails  :  @$emails;
                $phone = (!empty($bmiPoolData)) ? $bmiPoolData->phone   : @$phone;
                $language = (!empty($bmiPoolData)) ? $bmiPoolData->language   : 'EN';
                $country = (!empty($bmiPoolData)) ? $bmiPoolData->country   : '';
                $category = (!empty($bmiPoolData)) ? $bmiPoolData->category   :   '';
                $currency =  (!empty($bmiPoolData)) ? $bmiPoolData->currency   :'USD';
                $inf_price = (!empty($bmiPoolData)) ? $bmiPoolData->inf_price   :'0';
                $pitching_price = (!empty($bmiPoolData)) ? $bmiPoolData->pitching_price   :'0';
                $story_price = (!empty($bmiPoolData)) ? $bmiPoolData->story_price   :'0';
                $status = (!empty($bmiPoolData)) ? $bmiPoolData->status   :'0';
                $managedby = (!empty($bmiPoolData)) ? $bmiPoolData->managedby   :'';
                $added_by = (!empty($bmiPoolData)) ? $bmiPoolData->added_by   :'sonam.patel@nrt.co.in';
                $added_date = (!empty($bmiPoolData)) ? $bmiPoolData->added_date   :date('Y-m-d');
                $confirmed_on = (!empty($bmiPoolData)) ? $bmiPoolData->confirmed_on   : date('Y-m-d');
                $updated_date = (!empty($bmiPoolData)) ? $bmiPoolData->updated_date   : date('Y-m-d');
                $keyword_status = (!empty($bmiPoolData)) ? $bmiPoolData->keyword_status   : '0';
                $autotag_status = (!empty($bmiPoolData)) ? $bmiPoolData->autotag_status   : '0';
                $tags =(!empty($bmiPoolData)) ? $bmiPoolData->tags   : '';
                $tag_category_status =(!empty($bmiPoolData)) ? $bmiPoolData->tag_category_status   : '';
                $tag_category =(!empty($bmiPoolData)) ? $bmiPoolData->tag_category   :'';
                $inf_promotions =(!empty($bmiPoolData)) ? $bmiPoolData->inf_promotions   : '';
                $gender =(!empty($bmiPoolData)) ? $bmiPoolData->gender   : '';
                
                $credit_cost = (!empty($bmiPoolData)) ? 0   : '10';
                $image = (isset($account->profile_picture_url))? $account->profile_picture_url : @$bmiPoolData->image;

                $blur_image = null;
                if ($image) {
                    $file = $image;
                    $prImage = $account->username . '-blur-img.' . 'jpg';
                    $img = \Image::make($file);
                    $resource = $img->blur(50)->stream()->detach();
                    $storagePath = Storage::disk('s3')->put('channel/blur_img/' . $prImage, $resource, 'public');
                    $blur_image = Storage::disk('s3')->url('channel/blur_img/' . $prImage);
                }
                
                $internalChannel = InstagramData::where('username',$username)->first();
               
                if (empty($internalChannel)) {
                    $internalChannel = new InstagramData;
                }

                $internalChannel->username  = $username;
                $internalChannel->name = $name;
                $internalChannel->bio = $bio;
                $internalChannel->category = $category;
                $internalChannel->followers = $followers;
                $internalChannel->following = $following;
                $internalChannel->image = $image;
                $internalChannel->blur_image = $blur_image;
                $internalChannel->profile_post = $profile_post;
                $internalChannel->following_profile = $following_profile;
                $internalChannel->avg_likes = $avg_likes;
                $internalChannel->engmnt_rate = $engmnt_rate;
                $internalChannel->fair_price = $fair_price;
                $internalChannel->inf_score = $inf_score;
                $internalChannel->phone = $phone;
                $internalChannel->website = $website;
                $internalChannel->credit_cost = $credit_cost;
                $internalChannel->emails = $emails;
                $internalChannel->gender = $gender;
                $internalChannel->language = $language;
                $internalChannel->country = $country;
                $internalChannel->currency = $currency;
                $internalChannel->inf_price = $inf_price;
                $internalChannel->pitching_price = $pitching_price;
                $internalChannel->story_price = $story_price;
                $internalChannel->status = $status;
                $internalChannel->added_by = $added_by;
                $internalChannel->added_date = $added_date;
                $internalChannel->confirmed_on = $confirmed_on;
                $internalChannel->updated_date = $updated_date;
                $internalChannel->keyword_status = $keyword_status;
                $internalChannel->autotag_status = $autotag_status;
                $internalChannel->tags = $tags;
                $internalChannel->tag_category_status = $tag_category_status;
                $internalChannel->tag_category = $tag_category;
                $internalChannel->inf_promotions = $inf_promotions;
                $internalChannel->save();

                $ChannelAssociation = ChannelAssociation::where('influ_id', $user->id)->where('internal_channel_id', $internalChannel->id)->where('channel_association.plateform_type','2')->first();
                if (empty($ChannelAssociation)) {
                    $ChannelAssociation = new ChannelAssociation();
                }
                $ChannelAssociation->influ_id = $user->id;
                $ChannelAssociation->plateform_type ='2';
                $ChannelAssociation->internal_channel_id = $internalChannel->id;
                $ChannelAssociation->is_verified = 1;
                $ChannelAssociation->promotion_price = '1000';
                $ChannelAssociation->is_default = ($request->is_default) ? true : false;
                $ChannelAssociation->save();
                if ($request->is_default) {
                    $update_is_default = ChannelAssociation::where('influ_id', $user->id)->where('id', '!=', $ChannelAssociation->id)->where('channel_association.plateform_type','2')->update(['is_default' => false]);
                }


            }

            return 'true';
           
        }
        

        
         
        
    }

    public function generateIGToken(){
        $accessToken ="EAAOkE8myzpwBACWHFmxKmsKnVOD1KREkZBxQflmEeZAkJUbmXwMKwNOYeDjN9i9wbNWW6YWZCpvc8pZAgW1YbhYrR2mVoLwn3t6wgzrfPxegnbhmLZBE72JNWTyk8rhc1raWQ5DCDvcjb1FsjD357oU9APebTKkK2RZAdCARziCZAMfQRVzPTSM2UIul7uXAPUMG4glODwZBMwzQWiqM4iAZBZBaOtka2YdsiPSzDbZCXaxdwZDZD";
        $appId = $this->clientId;
        $appSecret = $this->clientSecret;

        $tokenEndpoint = 'https://graph.facebook.com/v13.0/oauth/access_token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $accessToken,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        $newAccessToken = $data['access_token'];
        $expiresIn = $data['expires_in'];
        return $newAccessToken;
    }

    
}