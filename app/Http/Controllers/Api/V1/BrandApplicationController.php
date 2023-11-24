<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use App\Models\User;
use App\Models\Message;
use App\Models\ChannelAssociation;
use Validator;
use Auth;
use Exception;
use DB;
use Mail;
use App\Mail\RejectApplicationMail;
use App\Mail\ShortListMail;
use Notification;
use App\Notifications\ActivityNotification;

class BrandApplicationController extends Controller {

    public function applicationResponses(Request $request) {

        try {
            $user = getUser();
            $channel_list =[];
            $limit = 10;
            $offset = 0;
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                    'camp_id' => 'required',
                   // 'plateform_type' => 'required|in:1,2',
                    ],
                    [
                        'camp_id.required' => 'Campaign id field is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
                $campaign = DB::table('campaigns')->where('id', $request->camp_id)->where('brand_id', $user->id)->whereIn('status', ['1','5'])->where('plateform_type',$plateform_type)->first();

                if (!is_object($campaign)) {
                    return prepareResult(false, "Campaign Not Found", [], $this->not_found);
                }
                
               
                $query =  AppliedCampaign::select('id','channel_id','camp_id','brand_id','currency','price','status')->where('camp_id',$request->camp_id)->where('brand_id',$user->id)->whereNotIn('status',['1','5'])->where('plateform_type',$plateform_type)->orderby('id','DESC');
                $totalCount = $query->count();
                $query = $query->orderby('id', 'DESC');
                if ($request->status!='') {
                    $query->where('applied_campaigns.status',''.$request->status.'');
                
                }
              
              
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $perPage = $perPage;
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

                $proposalCount =  DB::table('applied_campaigns')->select(
                    DB::raw('COUNT(IF(status != "1", 0, NULL)) as totalCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as shortlistCount'),
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as pendingCount'),
                    )->where('brand_id', $user->id)
                ->where('camp_id', $request->camp_id)
                ->where('plateform_type',$plateform_type)
                ->first();

                $messageCount = DB::table('messages')->where('receiver_id', $user->id)->where('is_read', '0')->count();
                

                if($plateform_type =='2'){
                    foreach ($channelList as $key => $data) {
                        $channel = DB::table('instagram_data')->where('id',$data->channel_id)->first();
                        $channel_association = ChannelAssociation::where('internal_channel_id', $data->channel_id)->where('plateform_type','2')->orderBy('id','DESC')->first();
                        
                        $influ_id = isset($channel_association->influ_id) ? $channel_association->influ_id : '2';

                        $vidoe_promotion_price = isset($channel_association->promotion_price) ? $channel_association->promotion_price : 0;
                        $is_favourite = false;
                        $favourite = \DB::table('favourites')->where('user_id', $user->id)->where('channel_id', $data->channel_id)->where('plateform_type','2')->first();
                        if (!empty($favourite)) {
                            $is_favourite = true;
                        }
                        $is_revealed = false;
                        $revealed =\DB::table('revealeds')->where('user_id', $user->id)->where('channel_id', $data->channel_id)->where('plateform_type','2')->first();
                        if (!empty($revealed)) {
                            $is_revealed = true;
                        }
                        $is_invite = 0;
                        $invite = \DB::table('invitations')->where('brand_id', $user->id)->where('influ_id', $influ_id)->where('channel_id', $data->channel_id)->where('plateform_type','2')->first();
                        if (!empty($invite)) {
                            $is_invite = 1;
                        }
                        $credit_cost = (!empty($channel->credit_cost)) ? $channel->credit_cost : 10;

                        $channel_list['channel_list'][] =[
                            "id" => $channel->id,
                            "application_id" => $data->id,
                            "influ_id" => $influ_id,
                            "channel_id" => $channel->id,
                            "channel_lang" => @$channel->language,

                            "name" => $channel->name,
                            "is_default" => (!empty($channel_association)) ? $channel_association->is_default : false,
                            "is_verified" => (!empty($channel_association)) ? $channel_association->is_verified : '0',
                            "promotion_price" => ($is_revealed == true) ? $vidoe_promotion_price : null,
                            "channel_link" => ($is_revealed == true) ? 'https://www.instagram.com/'.$channel->username : null,
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
                            "status" => $data->status,
                            "price" => $data->price,
                            "plateform_type" => $plateform_type,
                        ];

                    
                

                    }
                } else{
                    foreach ($channelList as $key => $data) {
                       $channel = DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','channel_email','blur_image','tag_category','tags','facebook','twitter','instagram','inf_recommend','price_view_ratio')->where('id',$data->channel_id)->first();

                        $channel_association =ChannelAssociation::select('id','internal_channel_id','promotion_price','influ_id','is_default','is_verified')->where('internal_channel_id', $channel->id)->where('plateform_type','1')->orderBy('id','DESC')->first();
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
                        $revealed = \DB::table('revealeds')->select('id')->where('user_id', $user->id)->where('channel_id',$channel->id)->where('plateform_type','1')->first();
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
                        $invite = \DB::table('invitations')->select('id')->where('brand_id', $user->id)->where('channel_id', $channel->id)->where('plateform_type','1')->first();
                        if (!empty($invite)) {
                            $is_invite = true;
                        }
                        $is_favourite = false;
                        $favourite = \DB::table('favourites')->select('id')->where('user_id', $user->id)->where('channel_id', $channel->id)->where('plateform_type','1')->first();
                        if (!empty($favourite)) {
                            $is_favourite = true;
                        }
                        $credit_cost = (!empty($channel->credit_cost)) ? $channel->credit_cost : 10;
                        $channel_link = "https://www.youtube.com/channel/".$channel->channel."";
                        $channel_list['applications'][] = [
                            "id" => $channel->id,
                            "application_id" => $data->id,
                            "influ_id" => (!empty($channel_association)) ? $channel_association->influ_id : '2',
                            "channel_id" => $channel->id,
                            "channel" => $channel->channel,
                            "channel_lang" => $channel->channel_lang,
                            "channel_link" => ($is_show == true ) ? $channel_link : null,
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
                            "status" => $data->status,
                            "price" => $data->price,
                            "plateform_type" => $plateform_type,
                            
                            ];
                        
                    }
                
                }
                
               
                $channel_list['campaign'] = $campaign;
                $channel_list['total'] = count($channelList);
                $channel_list['totalCount'] = $totalCount;
                $channel_list['shortlistCount'] = @$proposalCount->shortlistCount;
                $channel_list['pendingCount'] = @$proposalCount->pendingCount;
                $channel_list['messageCount'] = @$messageCount;
                $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
                $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
                $channel_list['last_page'] = $last_page;


                return prepareResult(true, 'Application responses', $channel_list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function applicationView(Request $request) {

        $id = $request->id;
        $user = getUser();
        $getApplication = [];
        $getApplication['campaign'] = DB::table('campaigns')->where('id', $id)
                ->first();

        $$user = getUser();
        $id = $request->id;
        $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
        if($plateform_type =='2'){
            $getApplication = AppliedCampaign::select(array('applied_campaigns.*','campaigns.*','applied_campaigns.id as id','applied_campaigns.price as price','applied_campaigns.currency as currency','instagram_data.id as channelid','campaigns.id as camp_id','applied_campaigns.status as status','instagram_data.username as title','instagram_data.bio as description','instagram_data.status as channel_status','campaigns.status as camp_status','applied_campaigns.created_at as created_at','campaigns.created_at as camp_created_at','applied_campaigns.comment as comment'))->leftjoin('instagram_data','instagram_data.id','applied_campaigns.channel_id')->leftjoin('campaigns','campaigns.id','applied_campaigns.camp_id')->where('applied_campaigns.id',$id)->where('applied_campaigns.brand_id',$user->id)->orderby('applied_campaigns.id','DESC');

            } else{
                $getApplication = AppliedCampaign::select(array('applied_campaigns.*','campaigns.*','applied_campaigns.id as id','applied_campaigns.price as price','applied_campaigns.currency as currency','channels.id as channelid','channels.channel_name as title','channels.yt_description as description','campaigns.id as camp_id','applied_campaigns.status as status','channels.status as channel_status','campaigns.status as camp_status','applied_campaigns.created_at as created_at','channels.created_at as channel_created_at','campaigns.created_at as camp_created_at','applied_campaigns.comment as comment'))->leftjoin('channels','channels.id','applied_campaigns.channel_id')->leftjoin('campaigns','campaigns.id','applied_campaigns.camp_id')->where('applied_campaigns.id',$id)->where('applied_campaigns.brand_id',$user->id)->orderby('applied_campaigns.id','DESC');

            }
           $getApplication = $getApplication->where('applied_campaigns.plateform_type',$plateform_type)->first();;
        if ($getApplication) {
            return prepareResult(true, 'Application view', $getApplication, $this->success);
        } else {
            return prepareResult(true, 'No Application Found', [], $this->not_found);
        }
    }

    public function applicationShortlist(Request $request) {

        $validator = Validator::make($request->all(), [
                    'id' => 'required',
                        ],
                        [
                            'id.required' => 'id field is required',
        ]);
        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }
        $id = $request->id;
        $user = getUser();
        $getApplication =AppliedCampaign::where('id', $id)->where('brand_id', $user->id)->with('campInfo:id,camp_title,brand_name,currency','infInfo')->first();
        try {
            if ($getApplication) {
                $shortList = AppliedCampaign::where('id',$id)->update(['status' => '2']);
                $message = new Message();
                $message->sender_id = $user->id;
                $message->receiver_id = $getApplication->influ_id;
                $message->channel_id = $getApplication->channel_id;
                $message->plateform_type = $getApplication->plateform_type;
                $message->message = 'Application for campaign ('.$getApplication->campInfo->camp_title.') is shortlisted ';
                $message->is_read = '0';
                $message->save();

                $content = [
                    "proposal" =>  $getApplication
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    Mail::to($getApplication->infInfo->email)->send(new ShortListMail($content));
                    
                }

                transactionHistory($getApplication->id, $getApplication->influ_id, '11', ' Application for campaign ('.$getApplication->campInfo->camp_title.') is shortlisted  ', '3', '0', '0', '0', '1', 'Your Application for campaign '.$getApplication->campInfo->camp_title.' is shortlisted by brand',$user->id, 'applied_campaigns', $getApplication->id,$getApplication->currency,NULL);


                return prepareResult(true, 'Application shortlisted', $getApplication, $this->success);
            } else {
                return prepareResult(true, 'No Application Found', [], $this->not_found);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function applicationReject(Request $request) {
        $validator = Validator::make($request->all(), [
                    'id' => 'required',
                        ],
                        [
                            'id.required' => 'id field is required',
        ]);
        if ($validator->fails()) {
            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
        }
        $id = $request->id;
        $user = getUser();
        try{
            $getApplication =AppliedCampaign::where('id', $id)->where('brand_id', $user->id)->with('campInfo:id,camp_title,brand_name,currency','infInfo')->first();
            if ($getApplication) {
                $rejected = AppliedCampaign::where('id', $id)->update(['status' => '3']);
                $userInfo = User::find($getApplication->brand_id);

                $message = new Message();
                $message->sender_id = $user->id;
                $message->receiver_id = $getApplication->influ_id;
                $message->channel_id = $getApplication->channel_id;
                $message->plateform_type = $getApplication->plateform_type;
                $message->message = 'Application  for campaign ('.$getApplication->campInfo->camp_title.') is rejected';
                $message->is_read = '0';
                $message->save();

                transactionHistory($getApplication->id, $getApplication->influ_id, '12', ' Application  for campaign ' . $getApplication->campInfo->camp_title . ' is rejected', '3', '0', '0', '0', '1', 'Your Application  for campaign (' . $getApplication->campInfo->camp_title . ') is rejected by brand', $user->id, 'applied_campaigns', $getApplication->id,$getApplication->currency,NULL);

               
                $content = [
                    "proposal" =>  $getApplication
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    Mail::to($getApplication->infInfo->email)->send(new RejectApplicationMail($content));
        
                }
                return prepareResult(true, 'Application rejected', $getApplication, $this->success);
            } else {
                return prepareResult(true, 'No Application Found', [], $this->not_found);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }



}
