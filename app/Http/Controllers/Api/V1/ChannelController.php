<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
use Alaouy\Youtube\Facades\Youtube;
use App\Rules\YoutubeRule;
use voku\helper\HtmlDomParser;
use Str;
use Illuminate\Support\Facades\Storage;
use Mail;
use App\Mail\ChannelMail;
use Phpfastcache\Helper\Psr16Adapter;
use InstagramScraper\Instagram;
//require __DIR__ . '/EmailVerification.php';
use EmailVerification;
class ChannelController extends Controller {

    public function channelList(Request $request) {
        try {
            $user = getUser();
            
            $channel_list = [];
            if ($user->roles[0]->name == 'influencer') {
                $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type  :'1';
                if($plateform_type =='2'){
                    $query = DB::table('channel_association')->join('instagram_data', 'channel_association.internal_channel_id', '=', 'instagram_data.id')->where('channel_association.influ_id', $user->id)
                                    ->where('instagram_data.username', '!=', '')
                                    ->where('instagram_data.status', '!=', '5')
                                    ->orderBy('channel_association.id', 'DESC');

                } else{
                    $query = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)
                                ->where('channels.channel_id', '!=', '')
                                ->where('channels.status', '!=', '2')
                                ->orderBy('channel_association.id', 'DESC');

                }
                $query->where('channel_association.plateform_type',$plateform_type);

                $totalCount = $query->count();   
                if(!empty($request->perPage))
                {
                    $perPage = $request->perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $channelList = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $channelList = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }
                if($plateform_type =='2'){

                    $channel_list['channel_list'] = $channelList;
                    $channel_list['totalCount'] = $totalCount;
                    $channel_list['total'] = count($channelList);
                    $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
                    $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
                    $channel_list['last_page'] = $last_page;
                    return prepareResult(true, "Instagram Account list", $channel_list, $this->success);

                }

                foreach ($channelList as $key => $channel) {
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

                    $ViewsPrice = $ViewsPrice;
                    $user_id = $this->base64url_encode($user->id);
                    $encodeUrl = urlencode('https://bmi.yt/'.$user_id.'/'. $channel->canonical_name .'');
                    $channel_link = "https://www.youtube.com/channel/".$channel->channel."";
                    $channel_list['channel_list'][] = [
                        "id" => $channel->internal_channel_id,
                        "channel_id" => $channel->channel_id,
                        "channel" => $channel->channel,
                        "canonical_name" => $channel->canonical_name,
                        "is_verified" => $channel->is_verified,
                        "channel_link" => $channel_link,
                        "promotion_price" => $channel->promotion_price,
                        "is_default" => $channel->is_default,
//                        "is_default" => $list->is_default,
                        "title" => $title,
                        "description" => $description,
                        "viewCount" => $viewCount,
                        "subscriberCount" => $subscriberCount,
                        "videoCount" => $videoCount,
                        "ViewsSubs" => $ViewsSubs,
                        "EngagementRate" => $EngagementRate,
                        "EstViews" => $EstViews,
                        "cpm" => $ViewsPrice,
                        "price_view_ratio" => ($channel) ? $channel->price_view_ratio : '',
                        "channel_email" => ($channel) ? $channel->channel_email : null,
                        "currency" => ($channel) ? $channel->currency : '',
                        "cat_percentile_1" => ($channel) ? $channel->cat_percentile_1 : '',
                        "cat_percentile_5" => ($channel) ? $channel->cat_percentile_5 : '',
                        "cat_percentile_10" => ($channel) ? $channel->cat_percentile_10 : '',
                        "profile_pic" => $channel->image,
                        "user_id" => $user_id,
                        "encodeUrl" => $encodeUrl,
                        "influ_id" => $channel->influ_id,
                        "plateform_type" => $plateform_type,
                    ];
                }
                $channel_list['totalCount'] = $totalCount;
                $channel_list['total'] = count($channelList);
                $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
                $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
                $channel_list['last_page'] = $last_page;

                return prepareResult(true, "Channel list", $channel_list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function addChannel(Request $request) {

        try {

            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {

                $validator = Validator::make($request->all(), [
                            'plateform' => 'required|in:youtube,instagram',
                            'channel_link' => ['required'],
                           // 'channel_lang' => 'required|in:Hindi,English',
                            'promotion_price' => 'required|numeric',
                                ],
                                [
                                    'plateform.required' => 'plateform  is required',
                                    'channel_link.required' => 'Channel Link  is required',
                                    //'channel_lang.required' => 'Channel  language required',
                                    'promotion_price.required' => 'Promotion price  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                if($request->plateform=='youtube'){

                    $channelUrl = "https://www.youtube.com/@" . $request->channel_link . "";
                    
                    $checkinternal_channel_id = checkYoutubeUrlValid($channelUrl);
                    if (!$checkinternal_channel_id) {
                        return prepareResult(false, 'This YouTube channel address is invalid', [], $this->unprocessableEntity);
                    }
                    $checkChannelAlready = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channels.channel', $checkinternal_channel_id)->where('channels.status', '!=', '2')->where('channel_association.plateform_type','1')->first();
                    if (is_object($checkChannelAlready)) {
                        return prepareResult(false, "channel Already Exist in your account", [], $this->not_found);
                    }

                    $bmiPoolChannel = ChannelSearchData::where('channel', $checkinternal_channel_id)->first();
                    if (empty($bmiPoolChannel)) {
                        $bmiPoolChannel = new ChannelSearchData;
                        $bmiPoolChannel->credit_cost = '10';
                        $bmiPoolChannel->confirmed = '9';
                    }
                    $activities = Youtube::getChannelById($checkinternal_channel_id);
                    $title = ($activities && isset($activities->snippet->title)) ? $activities->snippet->title : '';
                    $description = ($activities && isset($activities->snippet->description)) ? $activities->snippet->description : '';
                    $viewCount = ($activities && isset($activities->statistics->viewCount)) ? $activities->statistics->viewCount : '0';
                    $subscriberCount = ($activities && isset($activities->statistics->subscriberCount)) ? $activities->statistics->subscriberCount : '0';
                    $videoCount = ($activities && isset($activities->statistics->videoCount)) ? $activities->statistics->videoCount : '0';
                    $profile_pic = ($activities && isset($activities->snippet->thumbnails->high)) ? $activities->snippet->thumbnails->high->url : '';

                    $getVideos = $this->getLatestFiveVideo($checkinternal_channel_id);
                    $EstViews = round($getVideos['view_count'] / 5, 0);
                    $views_sub_ratio = ($subscriberCount != 0 ) ? round(($EstViews / $subscriberCount), 2) : '0';
                    $engRate = ($getVideos['view_count'] != 0 ) ? ($getVideos['like_count'] + $getVideos['dislike_count'] + $getVideos['comment_count']) / $getVideos['view_count'] : '0';
                    $engagementrate = round(($engRate / 5), 2);

                    $bmiPoolChannel->channel = $checkinternal_channel_id;
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
                    $internalChannel = Channel::where('channel', $checkinternal_channel_id)->first();
                    if (empty($internalChannel)) {
                        $internalChannel = new Channel;
                    }

                    $internalChannel->plateform = 'youtube';
                    $internalChannel->channel_id = $bmiPoolChannel->id;
                    $internalChannel->canonical_name = $request->channel_link;
                    $internalChannel->channel = $checkinternal_channel_id;
                    $internalChannel->channel_link = $channelUrl;
                    //$internalChannel->channel_lang = $request->channel_lang;
                    $internalChannel->channel_name = (isset($bmiPoolChannel) && $bmiPoolChannel) ? $bmiPoolChannel->channel_name : null;
                    $internalChannel->tag_category = (isset($bmiPoolChannel) && $bmiPoolChannel) ? $bmiPoolChannel->tag_category : null;
                    $internalChannel->tags = (isset($bmiPoolChannel) && $bmiPoolChannel) ? $bmiPoolChannel->top_two_tags : null;
                    $internalChannel->image = $image;
                    $internalChannel->blur_image = $blur_image;
                    $internalChannel->public_profile_url = '/youtube/' . $checkinternal_channel_id;

                    $internalChannel->exposure = $EstViews;

                    $internalChannel->status = '1';

                    $internalChannel->channel = $checkinternal_channel_id;
                    $internalChannel->channel_name = $title;
                    $internalChannel->yt_description = $description;
                    $internalChannel->views = $viewCount;
                    $internalChannel->subscribers = $subscriberCount;
                    $internalChannel->videos = $videoCount;
                    $internalChannel->image_path = $profile_pic;
                    $internalChannel->views_sub_ratio = $views_sub_ratio;
                    $internalChannel->engagementrate = $engagementrate;
                    $internalChannel->currency = $request->currency;
                    $internalChannel->credit_cost = $credit_cost;

                    $internalChannel->save();

                    $ChannelAssociation = ChannelAssociation::where('influ_id','2')->where('internal_channel_id', $internalChannel->id)->where('channel_association.plateform_type','1')->first();
                    if (empty($ChannelAssociation)) {
                        $ChannelAssociation = new ChannelAssociation();
                    }
                    $ChannelAssociation->influ_id = $user->id;
                    $ChannelAssociation->internal_channel_id = $internalChannel->id;
                    $ChannelAssociation->is_verified = 0;
                    $ChannelAssociation->promotion_price = $request->promotion_price;
                    $ChannelAssociation->is_default = ($request->is_default) ? true : false;
                    $ChannelAssociation->save();
                    if ($request->is_default) {
                        $update_is_default = ChannelAssociation::where('influ_id', $user->id)->where('id', '!=', $ChannelAssociation->id)->where('channel_association.plateform_type','1')->update(['is_default' => false]);
                    }
                    $content = [
                        "name" => $user->fullname,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to($user->email)->send(new ChannelMail($content));
                    }
                }
              
                if($request->plateform =='instagram'){
                    $is_account_true = false;
                    $bmiPoolData = PoolInstagramData::where('username',$request->channel_link)->first();
                    $account ='';
                    if(empty($bmiPoolData)){
                        $/*instagram = new \InstagramScraper\Instagram(new \GuzzleHttp\Client());
                        $instagram = \InstagramScraper\Instagram::withCredentials(new \GuzzleHttp\Client(), env('INSTAGRAM_USER'), env('INSTAGRAM_PASSWORD'), new Psr16Adapter('Files'));
                        $instagram->setUserAgent('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Firefox/78.0');
                        $emailVecification = new EmailVerification(
                            env('IMAP_CONNECTED_MAIL'),
                            env('IMAP_MAIL_SERVER'),
                            env('IMAP_MAIL_PASSWARD')
                        );
                        $instagram->login(false,true);
                        $instagram->saveSession();*/
                        
                         /* $accounts = $instagram->searchAccountsByUsername($request->channel_link,10);
                            $row = 0;
                            foreach ($accounts as $key => $account) {
                                if($request->channel_link == $account->getUsername()){
                                    
                                    $is_account_true = true;
                                    $row = $key;
                                } else{
        
                                }
                                
                            }*/
                             //$account_row = @$accounts[$row];
                             // $account = $instagram->getAccountInfo($request->channel_link);
                            $is_account_true = true;
                    } else{
                          $is_account_true = true;
                          $account ='';
                          
                    }
                    
                    
                    $checkinternal_channel_id = $this->checkInstagramValid($request->channel_link);
                    if ($is_account_true== false) {
                        return prepareResult(false, 'Instagram Profile not found', [], $this->unprocessableEntity);
                    }



                    $checkAccountAlready = DB::table('channel_association')->join('instagram_data', 'channel_association.internal_channel_id', '=', 'instagram_data.id')->where('channel_association.influ_id', $user->id)->where('instagram_data.username',$request->channel_link)->where('channel_association.plateform_type','2')->where('instagram_data.status', '!=', '5')->first();
                    if (is_object($checkAccountAlready)) {
                        return prepareResult(false, "Instgaram account Already Exist in your account", [], $this->not_found);
                    }
                     
            
                     if($account !=''){
                        $username= $request->channel_link;
                        $name= $request->channel_link;
                        $bio= '';
                        $followers= 0;
                        $following= 0;
                        $profile_post=0;
                        $emails = $user->email;
                        $phone= $user->phone;
                        $image= '';
                     } else{    
                        $username= $request->channel_link;
                        $name= $request->channel_link;
                        $bio= '';
                        $followers= '';
                        $following= '';
                        $profile_post= '';
                        $emails= $user->email;
                        $phone= $user->phone;
                        $image= '';
                    }
                    //$result = $this->getInstagramProfile($request->channel_link);

                    $username = (!empty($bmiPoolData)) ? $bmiPoolData->username : @$username;
                    $name = (!empty($bmiPoolData)) ? $bmiPoolData->name :  @$name;
                    $bio = (!empty($bmiPoolData)) ? $bmiPoolData->bio :   @$bio;
                    
                    $followers = (!empty($bmiPoolData)) ? $bmiPoolData->followers  : @$followers;
                    $following = (!empty($bmiPoolData)) ? $bmiPoolData->following  : @$following;
                    $profile_post =  (!empty($bmiPoolData)) ? $bmiPoolData->profile_post  : @$profile_post;
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

                    $blur_image = null;
                    if ($image) {
                        $file = $image;
                        $prImage = $username . '-blur-img.' . 'jpg';
                        $img = \Image::make($file);
                        $resource = $img->blur(50)->stream()->detach();
                        $storagePath = Storage::disk('s3')->put('channel/blur_img/' . $prImage, $resource, 'public');
                        $blur_image = Storage::disk('s3')->url('channel/blur_img/' . $prImage);
                    }
                    /*if (empty($bmiPoolData)) {
                        $bmiPoolData = new PoolInstagramData;
                        $bmiPoolData->username = $username;
                        $bmiPoolData->name = $name;
                        $bmiPoolData->bio = $bio;
                        $bmiPoolData->followers = $followers;
                        $bmiPoolData->following = $following;
                        $bmiPoolData->profile_post = $profile_post;
                        $bmiPoolData->emails = $emails;
                        $bmiPoolData->phone = $phone;
                        $bmiPoolData->category = $category;
                        $bmiPoolData->save() ;
                    }*/

                    $internalChannel = InstagramData::where('username',$request->channel_link)->first();
                   
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
                    $ChannelAssociation->promotion_price = $request->promotion_price;
                    $ChannelAssociation->is_default = ($request->is_default) ? true : false;
                    $ChannelAssociation->save();
                    if ($request->is_default) {
                        $update_is_default = ChannelAssociation::where('influ_id', $user->id)->where('id', '!=', $ChannelAssociation->id)->where('channel_association.plateform_type','2')->update(['is_default' => false]);
                    }
                    
                }
                
                transactionHistory($internalChannel->id, $user->id, '4', 'Instagram Account Added', '3', '0', '0', '0', '1', 'Instagram Account Added', $user->id, 'instagram_data', $internalChannel->id,$user->currency,NULL);


                return prepareResult(true, 'Added successfully', $internalChannel, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }
    public function getData($url){

        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, "https://www.instagram.com/cute_baby_trishaan/"); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
        $html = curl_exec($curl); 
        curl_close($curl); 
         
        // initialize HtmlDomParser 
        $htmlDomParser = HtmlDomParser::str_get_html($html);
        print_r($htmlDomParser);
        die;
    }

    function getProfileUserInfo($username) {

        $instagram = new \InstagramScraper\Instagram(new \GuzzleHttp\Client());
        $instagram = \InstagramScraper\Instagram::withCredentials(new \GuzzleHttp\Client(), env('INSTAGRAM_USER'), env('INSTAGRAM_PASSWORD'), new Psr16Adapter('Files'));
        $instagram->setUserAgent('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Firefox/78.0');
        $emailVecification = new EmailVerification(
            env('IMAP_CONNECTED_MAIL'),
            env('IMAP_MAIL_SERVER'),
            env('IMAP_MAIL_PASSWARD')
        );
        $instagram->login(false,true);
        $instagram->saveSession();
        try {

            $accounts = $instagram->searchAccountsByUsername($username);
            
            print_r($accounts);
            die;

        } catch (InstagramException $ex) {
            echo $ex->getMessage();
        }
        /*$access_token = env('INSTAGRAM_ACCESS_TOKEN');
        $client_id = '204495038865440';
        $ig_graph_url = 'https://api.instagram.com/v1/users/search?q='.$username.'&client_id='.$client_id.'';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_graph_url);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_graph_data = curl_exec($ch);

        curl_close ($ch);

        $ig_graph_data = json_decode($ig_graph_data, true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  

        print_r($http_code);
        die;*/
    }


    function getDetails($username) {
        $access_token = env('INSTAGRAM_ACCESS_TOKEN');
        $ig_graph_url = 'https://graph.instagram.com/me/search?q='.$username.'/&access_token='.$access_token;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_graph_url);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_graph_data = curl_exec($ch);

        curl_close ($ch);

        $ig_graph_data = json_decode($ig_graph_data, true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  

        print_r($http_code);
        die;
    }

    public function refreshIGToken($short_access_token){

        $short_access_token = env('INSTAGRAM_ACCESS_TOKEN');
        $client_secret = 'a985b925a435b14af91b512216da4d91';

        $ig_rtu = 'https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret='.$client_secret.'&access_token='.$short_access_token;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_rtu);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_new = curl_exec($ch);

        curl_close ($ch);

        $ig_new = json_decode($ig_new, true);

        if (!isset($ig_new['error'])) {

            $this->authData['access_token'] = $ig_new['access_token'];

            $this->authData['expires_in'] = $ig_new['expires_in'];

            DB::table('instagram_token')
            ->where('access_token', '<>', '')
            ->update([
                'access_token'  => $ig_new['access_token'],
                'valid_till'    => time(),
                'expires_in'    => $ig_new['expires_in']
            ]);

        }

    }

    public function checkInstagramValid($user){
       $api="https://i.instagram.com/api/v1/users/web_profile_info/?username=";

        $header="Mozilla/5.0 (Linux; Android 9; GM1903 Build/PKQ1.190110.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.143 Mobile Safari/537.36 Instagram 103.1.0.15.119 Android (28/9; 420dpi; 1080x2260; OnePlus; GM1903; OnePlus7; qcom; sv_SE; 164094539)";

        $ch = curl_init($api.$user);
        curl_setopt($ch, CURLOPT_USERAGENT, $header);
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.instagram.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
       // curl_setopt($ch, CURLOPT_HTTPHEADER, array("x-ig-app-id: 567067343352427"));
        $result = curl_exec($ch);
        curl_close($ch);
        $result=json_decode($result);
        $status= @$result->status;
        if (@$status=="ok"){
            
            return 'true';
        } else{
            return 'false';
        }
    }

    function getInstagramProfile($user) { 

        $api="https://i.instagram.com/api/v1/users/web_profile_info/?username=";
        $header="Mozilla/5.0 (Linux; Android 9; GM1903 Build/PKQ1.190110.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.143 Mobile Safari/537.36 Instagram 103.1.0.15.119 Android (28/9; 420dpi; 1080x2260; OnePlus; GM1903; OnePlus7; qcom; sv_SE; 164094539)";

        $ch = curl_init($api.$user);
        curl_setopt($ch, CURLOPT_USERAGENT, $header);
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.instagram.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("x-ig-app-id: 567067343352427"));
        $result = curl_exec($ch);
        curl_close($ch);
        $result=json_decode($result);
        $status=$result->status;

        if ($status=="ok")
        {
            return $result;

        }
        else

        {
           return '';
        }
    }

    function callInstagram($user) { 

        $api="https://i.instagram.com/api/v1/users/web_profile_info/?username=";
        $header="Mozilla/5.0 (Linux; Android 9; GM1903 Build/PKQ1.190110.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.143 Mobile Safari/537.36 Instagram 103.1.0.15.119 Android (28/9; 420dpi; 1080x2260; OnePlus; GM1903; OnePlus7; qcom; sv_SE; 164094539)";

        $ch = curl_init($api.$user);
        curl_setopt($ch, CURLOPT_USERAGENT, $header);
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.instagram.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("x-ig-app-id: 567067343352427"));
        $result = curl_exec($ch);
        curl_close($ch);
        $result=json_decode($result);
        $status=$result->status;

        if ($status=="ok")
        {
            $username =$result->data->user->username;
            $fullname=$result->data->user->full_name;
            $isprivate=$result->data->user->is_private;

            if ($isprivate==TRUE)
            {
                $isprivate="True";
            }
            else
            {
                $isprivate="No";
            }


           
            $isverified=$result->data->user->is_verified;

            if ($isverified==TRUE)
            {
                $isverified="Yes";
            }
            else
            {
                $isverified="No";
            }

            $url=$result->data->user->external_url;
            if ($url=="")
            {
                $url="No URL";
            }

            $biography=$result->data->user->biography;
            if ($biography=="")
            {
                $biography="No Biography";
            }
            $followercount=$result->data->user->edge_followed_by->count;
            $followingcount=$result->data->user->edge_follow->count;
            $totalpost=$result->data->user->edge_owner_to_timeline_media->count;

            $dp_hd=$result->data->user->profile_pic_url_hd;
            $dp=$result->data->user->profile_pic_url;
            echo "Name : <b>". $fullname."</b><br>";
            echo "Username :  <b>"."<a href='https://instagram.com/$username'>$username</a>"." </b><br>";
            echo "BioGraphy : <b>". $biography."</b><br>";
            echo "URL : <b>". "<a href='$url'>$url</a>"."</b><br>";
            echo "Is Verified Account : <b>". $isverified."</b><br>";
            echo "Is Private Account <b>: ". $isprivate."</b><br>";
            echo "Total Posts : <b>". $totalpost."</b><br>";
            echo "Total Followers : <b>". $followercount."</b><br>";
            echo "Total Following : <b>". $followingcount."</b><br>";
            echo "DP : <b><a href='$dp_hd'>High Quality</a> | <a href='$dp'>Normal Quality</a></b><br>";

            //print_r($result);
            print_r($result);

        }
        else

        {
            echo "Error";
        }
    }

    public function editChannel(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                            'promotion_price' => 'required|numeric',
                            'is_default' => 'required',
                                ], [
                            'promotion_price.required' => 'Promotion price  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->id;
                $checkChannel = DB::table('channel_association')->where('influ_id', $user->id)->where('internal_channel_id', $id)->first();
                if (!is_object($checkChannel)) {
                    return prepareResult(false, "Id Not Found", [], $this->not_found);
                }

                if(ucfirst($request->currency) == ucfirst('USD') &&  $request->promotion_price <'62'){
                    return prepareResult(false, "Promotion price should be $62 or greater then $62 ", [], $this->unprocessableEntity);
                } 
                if(ucfirst($request->currency) == ucfirst('INR') &&  $request->promotion_price <'5000 '){
                    return prepareResult(false, "Promotion price should be 5000 or greater then 5000 ", [], $this->unprocessableEntity);
                } 
                $ChannelAssociation = ChannelAssociation::find($checkChannel->id);
                $ChannelAssociation->promotion_price = $request->promotion_price;
                $ChannelAssociation->is_default = ($request->is_default) ? true : false;
                $ChannelAssociation->save();

                $internalChannel = Channel::find($ChannelAssociation->internal_channel_id);
                $internalChannel->currency = $request->currency;
                $internalChannel->save();

                if ($request->is_default) {
                    $update_is_default = ChannelAssociation::where('influ_id', $user->id)->where('id', '!=', $ChannelAssociation->id)->where('plateform_type',$ChannelAssociation->plateform_type)->update(['is_default' => false]);
                }

                $channelList = Channel::where('id', $id)->first();

                return prepareResult(true, 'Channel Updated', $channelList, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    function getLatestFiveVideo($YTChannelID) {
        $LatestvideoList = Youtube::searchChannelVideos('', $YTChannelID, 5, 'date');

        $like_count = 0;
        $dislike_count = 0;
        $comment_count = 0;
        $view_count = 0;
        $payLoadArray = [];

        foreach ($LatestvideoList as $key => $video) {

            $allvideos = Youtube::getVideoInfo($video->id->videoId);
           
            $like_count += (!empty($allvideos->statistics)) ? @$allvideos->statistics->likeCount : 0;
            $dislike_count += (!empty($allvideos->statistics))  ? @$allvideos->statistics->dislikeCount : 0;
            $comment_count += (!empty($allvideos->statistics))  ? @$allvideos->statistics->commentCount : 0;
            $view_count += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
        }

        $payLoadArray = [
            "like_count" => $like_count,
            "dislike_count" => $dislike_count,
            "comment_count" => $comment_count,
            "view_count" => $view_count,
        ];
        return $payLoadArray;
    }

    public function deleteChannel(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'channel_id' => 'required',
                            'plateform_type' => 'required|in:1,2',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->channel_id;

                $checkChannel = DB::table('channel_association')->where('influ_id', $user->id)->where('internal_channel_id', $id)->first();
                if (!is_object($checkChannel)) {
                    return prepareResult(false, "Channel not found", [], $this->not_found);
                }
                if ($checkChannel->is_default == '1') {
                    return prepareResult(false, "You can't delete this channel. Please make another channel the primary channel.", [], $this->unprocessableEntity);
                }
                $checkusedchannel = AppliedCampaign::where('influ_id', $user->id)->where('channel_id', $id)->get();
                if ($checkusedchannel->count() > 0) {
                    return prepareResult(false, "This channel cannot be deleted.", [], $this->unprocessableEntity);
                }
                if($request->plateform_type =='2'){
                    $channelUpdate = \DB::table('channel_association')->where('internal_channel_id',$id)->where('plateform_type','2')->delete();
                } else{
                    $channelUpdate = \DB::table('channel_association')->where('internal_channel_id',$id)->where('plateform_type','1')->delete();
                }
               
                return prepareResult(true, 'channel deleted', [], $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function verifyChannel(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'channel_id' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
               
                $channelUrl = "https://www.youtube.com/@" . $request->channel_id . "";
                
                $checkinternal_channel_id = checkYoutubeUrlValid($channelUrl);
                if (!$checkinternal_channel_id) {
                    return prepareResult(false, 'This is not a valid YouTube channel URL', [], $this->not_found);
                }
                $checkChannel = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')
                                ->where('channel_association.influ_id', $user->id)->where('channels.channel', $checkinternal_channel_id)->first();
                if (!is_object($checkChannel)) {
                    return prepareResult(false, "Channel Not Found", [], $this->not_found);
                }
                $youtube_url = $channelUrl . "/about";
                $data = file_get_contents($youtube_url);
                $html = htmlentities($data);
                $user_id = $user->id;
                $encodeUrl = urlencode('https://bmi.yt/'.$user_id.'/'. $request->channel_id .'');

                if (strpos($html, $encodeUrl) != false && (strpos($html, "bookmyinfluencers") != false || strpos($html, 'BookMyInfluencers') != false || strpos($html, 'BMI') != false)) {
                    ChannelAssociation::where('internal_channel_id', $checkChannel->id)
                    ->update(['is_verified' => 0, 'type' => 0]);
                    $channelAssociation = ChannelAssociation::where('influ_id', $user->id)->where('internal_channel_id', $checkChannel->id)->update(['is_verified' => '1', 'type' => 0]);

                    /*--------------assigne channel ownership to latest user------*/
                        $checkPreOwner = DB::table('channel_association')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')
                                ->where('channel_association.influ_id', $user->id)->where('channels.channel', $checkinternal_channel_id)
                            ->get();

                        if(count($checkPreOwner) >0 ){
                            foreach ($checkPreOwner as $key => $owner) {
                                $preUserDeactivate = User::where('id',$owner->influ_id)->whereNotIn('id',['2',$user->id])->update(['status'=>'0']);
                                
                            }
                            $allOrders = Order::where('influ_id',$owner->influ_id)->update(['influ_id'=>$user->id]);
                            $OrderProcess = OrderProcess::where('influ_id',$owner->influ_id)->update(['influ_id'=>$user->id]);
                            
                            
                        }

                    $verifyChannel = DB::table('channel_association')->where('internal_channel_id', $checkChannel->id)->first();
                    if ($verifyChannel) {
                        return prepareResult(true, "Channel Verified", $verifyChannel, $this->success);
                    } else {
                        return prepareResult(false, 'Opps! something went wrong', [], $this->internal_server_error);
                    }
                } else {
                    return prepareResult(false, 'Channel not verified', [], $this->unprocessableEntity);
                }
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

    function base64url_encode($str) {
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    function base64url_decode($str) {
        return base64_decode(strtr($str, '-_', '+/'));
    }

}
