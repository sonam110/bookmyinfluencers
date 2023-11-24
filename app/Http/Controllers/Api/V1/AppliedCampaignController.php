<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use App\Models\User;
use App\Models\Invitation;
use Validator;
use Auth;
use Exception;
use DB;
use Mail;
use App\Mail\CampaignApplyMail;
use Notification;
use App\Notifications\ActivityNotification;

class AppliedCampaignController extends Controller {

    public function getCampaign(Request $request) {
        try {
            $user = getUser();
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            if($plateform_type =='2'){
                $default_channel = DB::table('instagram_data')->select('instagram_data.id','instagram_data.followers','instagram_data.engmnt_rate','instagram_data.inf_score')->join('channel_association', 'instagram_data.id', '=', 'channel_association.internal_channel_id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_default', '1')->first();
                $followers = ($default_channel) ? $default_channel->followers : '0';
                $engmnt_rate = ($default_channel) ? $default_channel->engmnt_rate : '0';
                $inf_score = ($default_channel) ? $default_channel->inf_score : '0';
            }
            else{
                $default_channel = DB::table('channels')->select('channels.id','channels.subscribers','channels.engagementrate','channels.exposure')->join('channel_association', 'channels.id', '=', 'channel_association.internal_channel_id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_default', '1')->first();
                $subscribers = ($default_channel) ? $default_channel->subscribers : '0';
                $engagementrate = ($default_channel) ? $default_channel->engagementrate : '0';
                $avgviews = ($default_channel) ? $default_channel->exposure : '0';

            }

            if(@$request->channel_id!=''){
                $camp_ids =  DB::table('applied_campaigns')->where('influ_id', $user->id)->where('channel_id',@$request->channel_id)->where('plateform_type',$plateform_type)->pluck('camp_id')->toArray();
            } else{
                $camp_ids =  DB::table('applied_campaigns')->where('influ_id',$user->id)->where('plateform_type',$plateform_type)->pluck('camp_id')->toArray();
            }
           
            $inviteCamp_ids =DB::table('invitations')->where('influ_id',$user->id)->where('plateform_type',$plateform_type)->pluck('camp_id')->toArray();
            if($plateform_type =='2'){
                $query = Campaign::select(array('campaigns.*', DB::raw("(CASE WHEN followers >= " . $followers . " THEN True  ELSE False  END ) is_followers"), DB::raw("(CASE WHEN inf_score >= " . $inf_score . " THEN True  ELSE False  END ) is_inf_score"), DB::raw("(CASE WHEN engagement_rate >= " .
                                $engmnt_rate . " THEN True  ELSE False  END) is_engagement")))->whereNotIn('id',$camp_ids);
            
            } else{
                $query = Campaign::select(array('campaigns.*', DB::raw("(CASE WHEN subscriber >= " . $subscribers . " THEN True  ELSE False  END ) is_subscriber"), DB::raw("(CASE WHEN average_view >= " . $avgviews . " THEN True  ELSE False  END ) is_average_view"), DB::raw("(CASE WHEN engagement_rate >= " .
                                $engagementrate . " THEN True  ELSE False  END) is_engagement")))->whereNotIn('id',$camp_ids);

            }
            
            $query = $query->where('plateform_type',$plateform_type);
           
            if ($request->status!='') {
                if($request->status == '2'){
                    $query = Campaign::whereIn('id', $inviteCamp_ids)->whereNotIn('status', ['5']);
                } else{
                $query->whereNotIn('status', ['0', '3', '4','5'])
                    ->where('visibility', '1')
                    ->where('status',''.$request->status.'')
                    ->orderby('id', 'DESC');
                    
                }
            }
            else {
                $query->whereNotIn('status', ['0', '3', '4','5'])
                        ->where('visibility', '1')
                        ->orderby('id', 'DESC');
                        
            }
            if(@$request->budget!=''){
                $query->where('budget',$request->budget);
            }
            if(@$request->search_keyword !=''){
                $search_keyword = $request->search_keyword;
                $query->where('camp_title', 'LIKE', "%{$search_keyword}%")->orWhere('brand_name', 'LIKE', "%{$search_keyword}%")->orWhere('camp_desc', 'LIKE', "%{$search_keyword}%")->whereRaw("find_in_set('".$search_keyword."',category)")->whereRaw("find_in_set('".$search_keyword."',tags)");
            }
            $liveCount =  DB::table('campaigns')->whereNotIn('id', $camp_ids)->where('status', '1')->where('visibility', '1')->where('plateform_type',$plateform_type)->count();
            $InvitedCount =  DB::table('campaigns')->whereIn('id', $inviteCamp_ids)->where('plateform_type',$plateform_type)->whereNotIn('status', ['5'])->count();
            $totalCount = $liveCount + $InvitedCount;
            
            if(!empty($request->perPage))
                {
                    $perPage = $request->perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                    $pagination =  [
                        'campaign' => $result,
                        'liveCount' => @$liveCount,
                        'InvitedCount' => @$InvitedCount,
                        'totalCount' => @$totalCount,
                        'total' => $total,
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'last_page' => ceil($total / $perPage),
                    ];
                    $query = $pagination;
                }
                else
                {
                    $query =  [
                        'campaign' => $query->get(),
                        'liveCount' => @$liveCount,
                        'InvitedCount' => @$InvitedCount,
                        'totalCount' => @$totalCount,
                    ];
                }



            return prepareResult(true, 'Campaign List', $query, $this->success);
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function viewCampaign(Request $request) {

        $id = $request->id;
        $getCampaign =  Campaign::where('id', $id)->where('status', '!=', '0')->first();
        if ($getCampaign) {
            return prepareResult(true, 'Campaign view', $getCampaign, $this->success);
        } else {
            return prepareResult(true, 'No Campaign Found', [], $this->not_found);
        }
    }

    public function appliedCampaign(Request $request) {
        try {

            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {

                $validator = Validator::make($request->all(), [
                            'camp_id' => 'required',
                            'channel_id' => 'required',
                            'price' => 'required|numeric',
                                ],
                                [
                                    'camp_id.required' => 'Campaign id field is required',
                                    'channel_id.required' => 'Please select channel',
                                    'price.required' => 'Price field is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                if ($request->plateform_type=='1') {
                    $plateform_type ='youtube';
                
                }  else{
                    $plateform_type ='instagram';
                }

                $checkEmp = DB::table('applied_campaigns')->where('influ_id', $user->id)->where('camp_id', $request->camp_id)->where('channel_id', $request->channel_id)->where('plateform_type',$request->plateform_type)->first();
                if (is_object($checkEmp)) {
                    return prepareResult(false, "You have already applied for this campaign", [], $this->bad_request);
                }
                $checkCamp =  DB::table('campaigns')->where('id', $request->camp_id)->where('plateform',$plateform_type)->where('status', '1')->first();
                $invitation = Invitation::where('camp_id', $request->camp_id)->where('channel_id', $request->channel_id)->where('influ_id', $user->id)->where('plateform_type',$request->plateform_type)->first();
                if (is_object($checkCamp) && $checkCamp->invite_only == 1 && !is_object($invitation)) {
                    return prepareResult(false, "This campaign is invitation-only..", [], $this->not_found);
                }
//                if (is_object($checkCamp)) {
//                    return prepareResult(false, "Campaign Not Found", [], $this->not_found);
//                }
                if (!is_object($checkCamp)) {
                    return prepareResult(false, "Campaign Not Found", [], $this->not_found);
                }
                if($request->plateform_type=="2"){
                     $checkChannel = ChannelAssociation::join('instagram_data', 'channel_association.internal_channel_id', '=', 'instagram_data.id')->where('channel_association.influ_id', $user->id)->where('instagram_data.id', $request->channel_id)->where('channel_association.plateform_type',$request->plateform_type)->first();
                } else{
                    $checkChannel = ChannelAssociation::join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channels.id', $request->channel_id)->where('channel_association.plateform_type',$request->plateform_type)->first();
                }
                
                if (!is_object($checkChannel)) {
                    return prepareResult(false, "Channel Not Found", [], $this->not_found);
                }
                if ($checkChannel->is_verified == '0') {
                    return prepareResult(false, "Please verify your channel", $checkChannel, $this->channel_not_verify);
                }
                $condArray = ['Dedicated Video Promotion','Short Video Promotion'];
                if(!in_array($checkCamp->promot_product,$condArray) && $request->plateform_type =='1'){
                    if ($request->old_duration == 0) {
                        $validator = Validator::make($request->all(), [
                            'new_duration' => 'required',
                        ],
                        [
                            'new_duration.required' => 'Please select duration',
                        ]);
                        if ($validator->fails()) {
                            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                        }
                    }
                    if ($request->promotion_slot == 0) {
                        $validator = Validator::make($request->all(), [
                            'new_promotion_slot' => 'required',
                        ],
                        [
                            'new_promotion_slot.required' => 'Please select new prefrence',
                        ]);
                        if ($validator->fails()) {
                            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                        }
                    }

                }



                /*if ($request->view_commitment == '1') {
                    $validator = Validator::make($request->all(), [
                        'min_views' => 'required',
                    ],
                    [
                        'min_views.required' => 'Min View is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }*/

                if ($request->delivery_days == '0') {
                    $validator = Validator::make($request->all(), [
                        'other_delivery_days' => 'required',
                    ],
                    [
                        'other_delivery_days.required' => 'Please enter delivery days',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                $validator = Validator::make($request->all(), [
                    'privacy_policy' => 'required',
                ],
                [
                    'privacy_policy.required' => 'Please check Privacy terms and condition ',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $campaign = new AppliedCampaign;
                $campaign->influ_id = $user->id;
                $campaign->camp_id = $request->camp_id;
                $campaign->brand_id = $checkCamp->brand_id;
                $campaign->channel_id = $request->channel_id;
                $campaign->plateform_type = $request->plateform_type;
                $campaign->old_duration = ($request->old_duration) ? 1 : 0;
                $campaign->new_duration = ($request->old_duration == '0' ) ? $request->new_duration : null;
                $campaign->promotion_slot = ($request->promotion_slot) ? 1 : 0;
                $campaign->new_promotion_slot = ($request->promotion_slot == '0' ) ? $request->new_promotion_slot : null;
                $campaign->price = $request->price;
                $campaign->currency = @$request->currency;
                $campaign->view_commitment = ($request->view_commitment) ? 1 : 0;
                $campaign->min_views = ($request->view_commitment == '1' ) ? $request->min_views : null;
                $campaign->minor_changes = ($request->minor_changes) ? 1 : 0;
                $campaign->delivery_days =  $request->delivery_days;
                $campaign->other_delivery_days = ($request->delivery_days == '0' ) ? $request->other_delivery_days : null;
                $campaign->social_media_share = ($request->social_media_share) ? 1 : 0;
                $campaign->social_media = ($request->social_media_share == '1' ) ? $request->social_media : null;
                $campaign->privacy_policy = ($request->privacy_policy) ? $request->privacy_policy : 0;
                $campaign->comment = $request->comment;
                $campaign->save();

                transactionHistory($campaign->id, $checkCamp->brand_id,'', '5', 'New Proposal Received', '3', '0', '0', '0', '1', 'Influencer apply for campaign', $user->id, ' applied_campaigns', $user->id,@$request->currency,NULL);
                $campaignPost = AppliedCampaign::where('id', $campaign->id)->first();
                $userInfo = User::find($checkCamp->brand_id);
                $content = [
                    "proposal" => $campaignPost
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    $recevier = Mail::to(mailSendTo($userInfo->id))->send(new CampaignApplyMail($content));
                }
                Notification::send($userInfo, new ActivityNotification($campaignPost));
                return prepareResult(true, 'Proposal submitted successfully', $campaign, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function editApplication(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                            'camp_id' => 'required',
                            'channel_id' => 'required',
                            'price' => 'required|numeric',
                                ],
                                [
                                    'id.required' => 'Id field is required',
                                    'camp_id.required' => 'Campaign id field is required',
                                    'channel_id.required' => 'Please select channel',
                                    'price.required' => 'Price field is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                if ($request->plateform_type=='1') {
                    $plateform_type ='youtube';
                
                }  else{
                    $plateform_type ='instagram';
                }
                $checkCamp = DB::table('campaigns')->where('id', $request->camp_id)->where('status', '1')->where('plateform',$plateform_type)->first();
                if (!is_object($checkCamp)) {
                    return prepareResult(false, "Campaign Not Found", [], $this->not_found);
                }
                
                $checkEmp = DB::table('applied_campaigns')->where('influ_id', $user->id)->where('camp_id', $request->camp_id)->where('channel_id', $request->channel_id)->where('id','!=',$request->id)->where('plateform_type',$request->plateform_type)->first();
                if (is_object($checkEmp)) {
                    return prepareResult(false, "You have already applied for this campaign", [], $this->bad_request);
                }
                
                if($request->plateform_type=="2"){
                     $checkChannel = ChannelAssociation::join('instagram_data', 'channel_association.internal_channel_id', '=', 'instagram_data.id')->where('channel_association.influ_id', $user->id)->where('instagram_data.id', $request->channel_id)->where('channel_association.plateform_type',$request->plateform_type)->first();
                } else{
                    $checkChannel = ChannelAssociation::join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $user->id)->where('channels.id', $request->channel_id)->where('channel_association.plateform_type',$request->plateform_type)->first();
                }
                if (!is_object($checkChannel)) {
                    return prepareResult(false, "Channel Not Found", [], $this->not_found);
                }
                if ($checkChannel->is_verified == '0') {
                    return prepareResult(false, "Please verify your channel", $checkChannel, $this->unprocessableEntity);
                }
                $condArray = ['Dedicated Video Promotion','Short Video Promotion'];
                if(!in_array($checkCamp->promot_product,$condArray) && $request->plateform_type =='1'){
                    if ($request->old_duration == 0) {
                        $validator = Validator::make($request->all(), [
                                    'new_duration' => 'required',
                                        ],
                                        [
                                            'new_duration.required' => 'Please select duration',
                        ]);
                        if ($validator->fails()) {
                            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                        }
                    }
                    if ($request->promotion_slot == 0) {
                        $validator = Validator::make($request->all(), [
                                    'new_promotion_slot' => 'required',
                                        ],
                                        [
                                            'new_promotion_slot.required' => 'Please select new prefrence',
                        ]);
                        if ($validator->fails()) {
                            return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                        }
                    }

                }


                $validator = Validator::make($request->all(), [
                            'price' => 'required',
                                ],
                                [
                                    'price.required' => 'Price is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                /*if ($request->view_commitment == '1') {
                    $validator = Validator::make($request->all(), [
                                'min_views' => 'required',
                                    ],
                                    [
                                        'min_views.required' => 'Min View is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }*/

                if ($request->delivery_days == '0') {
                    $validator = Validator::make($request->all(), [
                                'other_delivery_days' => 'required',
                                    ],
                                    [
                                        'other_delivery_days.required' => 'Please enter delivery days',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                $validator = Validator::make($request->all(), [
                            'privacy_policy' => 'required',
                                ],
                                [
                                    'privacy_policy.required' => 'Please select Privacy terms and condition ',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $campaign = AppliedCampaign::find($request->id);
                $campaign->influ_id = $user->id;
                $campaign->camp_id = $request->camp_id;
                $campaign->plateform_type = $request->plateform_type;
                $campaign->brand_id = $checkCamp->brand_id;
                $campaign->channel_id = $request->channel_id;
                $campaign->old_duration = ($request->old_duration) ? 1 : 0;
                $campaign->new_duration = ($request->old_duration == '0' ) ? $request->new_duration : null;
                $campaign->promotion_slot = ($request->promotion_slot) ? 1 : 0;
                $campaign->new_promotion_slot = ($request->promotion_slot == '0' ) ? $request->new_promotion_slot : null;
                $campaign->price = $request->price;
                $campaign->view_commitment = ($request->view_commitment) ? 1 : 0;
                $campaign->min_views = ($request->view_commitment == '1' ) ? $request->min_views : null;
                $campaign->minor_changes = ($request->minor_changes) ? 1 : 0;
                $campaign->delivery_days = $request->delivery_days;
                $campaign->other_delivery_days = ($request->delivery_days == '0' ) ? $request->other_delivery_days : null;
                $campaign->social_media_share = ($request->social_media_share) ? 1 : 0;
                $campaign->social_media = ($request->social_media_share == '1' ) ? $request->social_media : null;
                $campaign->privacy_policy = ($request->privacy_policy) ? $request->privacy_policy : 0;
                $campaign->comment = $request->comment;
                $campaign->save();
                
                return prepareResult(true, 'Proposal updated', $campaign, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

   

}
