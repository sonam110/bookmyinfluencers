<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invitation;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use App\Models\Revealed;
use App\Models\WhatsAppRequest;
use App\Models\InvoiceDownload;
use App\Models\InstagramData;
use App\Models\listing;
use App\Models\User;
use Validator;
use Exception;
use Mail;
use App\Mail\InvitationMail;
use App\Mail\WhatsAppRequestMail;
use App\Mail\WhatsAppRequestBrandMail;
use DB;
class InviteController extends Controller {

    public function inviteCampaignList(Request $request) {
        try {
            $user = getUser();
            
            $campaignList = [];
            if ($user->roles[0]->name == 'brand') {
                $query = DB::table('campaigns')->where('brand_id', $user->id)
                        ->where('status', '2')
                        ->orderBy('id', 'DESC');
                if(!empty($request->perPage))
                {
                    $perPage = $request->perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                    $pagination =  [
                        'invitations' => $result,
                        'totalCount' => $total,
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
                        'invitations' => $query->get(),
                        'totalCount' => $query->count(),
                    ];
                }

                return prepareResult(true, "Invite Campaign list", $query, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function sendInvitation(Request $request) {
        try {

           
            $validator = Validator::make($request->all(), [
                'channel_id' => 'required',
                'camp_id' => 'required',
                'plateform_type' => 'required|in:1,2',
                    ],
                    [
                'channel_id.required' => 'Channel id is required',
                'camp_id.required' => 'campaign id  is required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $user = getUser();
            $payload = [];
            if ($request->plateform_type=='2') {
                $type= 'instagram';
            }  else{
                $type= 'youtube';
            }

            $checkCamp = DB::table('campaigns')->where('brand_id', $user->id)->where('id', $request->camp_id)->where('plateform',$type)->first();
            
            if (!is_object($checkCamp)) {
                return prepareResult(false, "Invite Campaign Not Found", [], $this->not_found);
            }
            $is_valid = false;
            if(!empty($request->influ_id)){
                
                $checkAlredayInvite = \DB::table('invitations')->where('channel_id',$request->channel_id)->where('camp_id', $request->camp_id)->where('brand_id', $user->id)->where('plateform_type',$request->plateform_type)->first();
                if (is_object($checkAlredayInvite)) {
                    $is_valid = false;
                    return prepareResult(false, "You've already invited to all influencers for this campaign ".$checkCamp->camp_title."", [], $this->unprocessableEntity);
                } else{
                    $is_valid = true;
                }
            } else{
                $is_valid = true;
            }

            
            if($is_valid == true){
                if($request->channel_id =='All'){
                    $allReadyInviteChannel = DB::table('invitations')->where('camp_id', $request->camp_id)->where('brand_id', $user->id)->where('plateform_type',$request->plateform_type)->pluck('channel_id')->toArray();
                    $revealedChannel = DB::table('revealeds')->where('user_id',$user->id)->whereNotIn('channel_id',$allReadyInviteChannel)->where('plateform_type',$request->plateform_type)->pluck('channel_id')->toArray();
                    $internal_channel_ids = $revealedChannel;
                    if(count($revealedChannel) <1){
                        return prepareResult(false, "You've already invited this influencer to campaign ".$checkCamp->camp_title."", [], $this->unprocessableEntity);
                    }
                } else{
                    $internal_channel_ids = explode(',', $request->channel_id);
                }
                
               
                foreach ($internal_channel_ids as $key => $channel) {
                    $channel_association = ChannelAssociation::where('is_verified', 1)->where('internal_channel_id', $channel)->where('plateform_type',$request->plateform_type)->orderBy('id','DESC')->orderBy('id','DESC')->first();
                    if ($request->plateform_type=='2') {
                        $channel_d = InstagramData::select('id','username')->where('id', $channel)->first();
                        $channel_name = @$channel_d->username;
                    } else{
                        $channel_d = Channel::select('id','channel_name')->where('id', $channel)->first();
                        $channel_name = @$channel_d->channel_name;
                    }
                    
                    $influ_id = (!empty($channel_association)) ? $channel_association->influ_id : '2';
                    $invitation = Invitation::where('channel_id', $channel)->where('camp_id', $request->camp_id)->where('brand_id', $user->id)->where('plateform_type',$request->plateform_type)->first();
                    if (empty($invitation)) {
                        $invitation = new Invitation;
                    }

                    $invitation->brand_id = $user->id;
                    $invitation->camp_id = $request->camp_id;
                    $invitation->influ_id = $influ_id;
                    $invitation->channel_id = $channel;
                    $invitation->plateform_type = $request->plateform_type;
                    $invitation->save();
                    
                     
                    $userInfo =User::where('id',$influ_id)->first();
                    $email = @$userInfo->email;
                    /*if(!empty(@$channel_d->email)){
                       $email = $email;
                    }*/
                    
                    $baseRedirURL = env('APP_URL');
                   // $link = $baseRedirURL."/campaign-detail/" . $checkCamp->uuid . "/" . base64url_encode($email) . "";
                    $link = $baseRedirURL."/apps/campaigns/all";
                    $anchor_link = ''.$channel_name.' received an invitation for a campaign '.$checkCamp->camp_title.'';
                    $content = [
                        "user" => $userInfo,
                        "email" => $email,
                        "link" => $link,
                        "channel_name" => @$channel_name,
                        "campaign" => Campaign::where('id', $request->camp_id)->first()
                    ];
                    
                    if (env('IS_MAIL_ENABLE', true) == true) {

                        $recevier = Mail::to(mailSendTo($influ_id))->send(new InvitationMail($content));
                    }
                    
                    transactionHistory($invitation->id, $influ_id, '6', ''.$anchor_link.'', '3', '0', '0', '0', '1', '', $user->id, 'invitations', $invitation->id,$user->currency,NULL);
                }
                $getList = Invitation::where('brand_id', $user->id)->where('camp_id', $request->camp_id)->where('plateform_type',$request->plateform_type)->get();
                return prepareResult(true, 'Invitation sent successfully', $getList, $this->success);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function invitationList(Request $request) {

        try {
            $user = getUser();
            $query = Invitation::orderBy('id', 'DESC');
            if ($user->roles[0]->name == 'influencer') {
                $query = $query->where('influ_id', $user->id)->with('campInfo');
            }
            if ($user->roles[0]->name == 'brand') {
                $query = $query->where('brand_id', $user->id)->with('campInfo');
            }
            if(!empty($request->perPage))
            {
                $perPage = $request->perPage;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination =  [
                    'invitations' => $result,
                    'totalCount' => $total,
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
                    'invitations' => $query->get(),
                    'totalCount' => $query->count(),
                ];
            }
            
            return prepareResult(true, "Invitation  list", $query, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function campInvitation(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'camp_id' => 'required',
                            'plateform_type' => 'required|in:1,2',
                                ],
                                [
                                    'camp_id.required' => 'Campaign id field is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

            $channel_list = [];
            $channel_list['campaign'] = DB::table('campaigns')->where('id', $request->camp_id)->where('brand_id', $user->id)->where('plateform_type',$request->plateform_type)->first();
            $invitationId = DB::table('invitations')->select()->where('camp_id', $request->camp_id)->where('brand_id', $user->id)->where('plateform_type',$request->plateform_type)->pluck('channel_id')->toArray();
            if($request->plateform_type =='2'){
                $query = DB::table('instagram_data')->whereIn('id',$invitationId)->orderby('id','DESC');
                
            } else{
                $query = DB::table('channels')->whereIn('id',$invitationId);

            }

            $totalCount = $query->count();
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
            if($request->plateform_type =='2'){
                $channel_list['influencers'] =  instagramData($channelList,$request->plateform_type);
            } else{
                $channel_list['influencers'] =  youtubeData($channelList,$request->plateform_type);
            }
            
            $channel_list['total'] = count($channelList);
            $channel_list['totalCount'] = $totalCount;
            $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
            $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
            $channel_list['last_page'] = $last_page;

            return prepareResult(true, "Invitation  list", $channel_list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function inviteInfluencers(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), ['camp_id' => 'required',], ['camp_id.required' => 'campaign id  is required',]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $channel_list = [];
                $campCategory = Campaign::where('id', $request->camp_id)->where('brand_id', $user->id)->first();
                if (!is_object($campCategory)) {
                    return prepareResult(false, "Campaign Not Found", [], $this->not_found);
                }
                $categories = (isset($campCategory) && $campCategory ) ? $campCategory->category : null;

                //$categories = explode(',', $category);
                $revealedChannel = \DB::table('revealeds')->where('user_id', $user->id)->pluck('channel_id')->implode(',');
                $revealed = explode(',', $revealedChannel);
                $channelList = Channel::select('id','channel_id','tag_category','status')->where('channel_id', '!=', '')
                        ->whereIn('id', $revealed)
                        ->whereIn('tag_category', $categories)
                        ->where('status', '1')
                        ->get();
                foreach ($channelList as $key => $channel) {

                    $channel_association = ChannelAssociation::select('id','internal_channel_id','promotion_price','influ_id','is_default','is_verified')->where('internal_channel_id', $channel->id)->orderBy('id','DESC')->first();
                    $influ_id = (!empty($channel_association)) ? $channel_association->influ_id : '2';
                    $is_revealed = false;
                    $revealed = Revealed::where('user_id', $user->id)->where('channel_id', $channel->id)->first();
                    if ($revealed) {
                        $is_revealed = true;
                    }

                    $title = ($channel && isset($channel->channel_name)) ? $channel->channel_name : '';
                    $channel_list[] = [
                        "id" => $channel->id,
                        "influ_id" => $influ_id,
                        "channel_id" => $channel->id,
                        "channel" => ($is_revealed == true) ? $channel->channel : null,
                        "title" => ($is_revealed == true) ? $title : null,
                    ];
                }

                return prepareResult(true, "Channel List", $channel_list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function whatsappRequest(Request $request) {
        try {
            $user = getUser();
            $validator = Validator::make($request->all(), [
                        'channel_id' => 'required|exists:channels,id',
                            ],
                            [
                                'channel_id.required' => 'Channel id is required'
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }

            $saveRequest = new WhatsAppRequest;
            $saveRequest->brand_id = $user->id;
            $saveRequest->channel_id = $request->channel_id;
            $saveRequest->plateform_type = $request->plateform_type;
            $saveRequest->save();
            $transaction_id = \Str::random(10);

            if($request->plateform_type=='2'){
                $channel_d = DB::table('instagram_data')->select('id','username')->where('id',$request->channel_id)->first();
                $channel_name = @$channel_d->username;
            } else{
                $channel_d = DB::table('channels')->select('id','channel_name')->where('id', $request->channel_id)->first();
                $channel_name = @$channel_d->channel_name;
            }
            $influInfo = \DB::table('channel_association')->where('internal_channel_id',$request->channel_id)->where('plateform_type',$request->plateform_type)->where('is_verified','1')->where('influ_id','!=','2')->first();

            $influ_id = (!empty($influInfo)) ? $influInfo->influ_id:'2';

            transactionHistory($transaction_id, $influ_id, '12', 'WhatsApp request sent by brand '.$user->fullname,'', '3', '0',0,0, '1', '', $user->id, 'whats_app_requests', $saveRequest->id,$user->currency,NULL);

            if (env('IS_MAIL_ENABLE', true) == true) {

                
                $content = [
                    "user" => User::where('id', $user->id)->first(),
                    "channel_name" => $channel_name,
                    "channel_link" => $channel_name,
                ];
                $mailTomanage = Mail::to(env('MANAGER_MAIL', 'abhishek@prchitects.com'))->send(new WhatsAppRequestMail($content));
                $content1 = [
                    "user" => User::where('id', $user->id)->first(),
                ];
                $maintoBrand = Mail::to($user->email)->send(new WhatsAppRequestBrandMail($content1));
            }

            return prepareResult(true, 'Request placed successfully', $saveRequest, $this->success);
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

     public function sendInviteList(Request $request) {
        try {
           
            $validator = Validator::make($request->all(), [
                'list_id' => 'required|exists:create_lists,id',
                'camp_id' => 'required',
                'plateform_type' => 'required:in:1,2',
                    ],
                    [
                'list_id.required' => 'List id is required',
                'camp_id.required' => 'campaign id  is required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $user = getUser();
            $payload = [];

            $checkCamp = DB::table('campaigns')->where('brand_id', $user->id)->where('id', $request->camp_id)->where('plateform_type',$request->plateform_type)->first();
            if (!is_object($checkCamp)) {
                return prepareResult(false, " Campaign Not Found", [], $this->not_found);
            }
           
            $internal_channel_ids = listing::where('list_id',$request->list_id)->where('plateform_type',$request->plateform_type)->pluck('channel_id')->toArray();
            
            if($internal_channel_ids){
                
                foreach ($internal_channel_ids as $key => $channel) {
                    $channel_association = ChannelAssociation::where('is_verified', 1)->where('internal_channel_id', $channel)->where('plateform_type',$request->plateform_type)->orderBy('id','DESC')->first();
                    
                    if($checkCamp->plateform_type=='2'){
                        $channel_d = DB::table('instagram_data')->select('id','username')->where('id',$channel)->first();
                        $channel_name = @$channel_d->username;
                    } else{
                        $channel_d = DB::table('channels')->select('id','channel_name')->where('id', $channel)->first();
                        $channel_name = @$channel_d->channel_name;
                    }
                    
                    $influ_id = (!empty($channel_association)) ? $channel_association->influ_id : '2';
                    $invitation = Invitation::where('channel_id', $channel)->where('camp_id', $request->camp_id)->where('brand_id', $user->id)->where('plateform_type',$request->plateform_type)->first();
                    if (empty($invitation)) {
                        $invitation = new Invitation;
                    }

                    $invitation->brand_id = $user->id;
                    $invitation->camp_id = $request->camp_id;
                    $invitation->influ_id = $influ_id;
                    $invitation->channel_id = $channel;
                    $invitation->plateform_type = $request->plateform_type;
                    $invitation->save();
                    $userInfo =User::where('id', $influ_id)->first();
                    $email = @$userInfo->email;
                    /*if(!empty(@$channel_d->email)){
                       $email = $email;
                    }*/
                    
                    $baseRedirURL = env('APP_URL');
                   // $link = $baseRedirURL."/campaign-detail/" . $checkCamp->uuid . "/" . base64url_encode($email) . "";
                    $link = $baseRedirURL."/apps/campaigns/all";
                    $anchor_link = ''.$channel_name.' received an invitation for a campaign '.$checkCamp->camp_title.'';
                    $content = [
                        "user" => $userInfo,
                        "email" => $email,
                        "link" => $link,
                        "channel_name" => ($checkCamp->plateform_type=='2') ? @$channel_d->username : @$channel_d->channel_name,
                        "campaign" => Campaign::where('id', $request->camp_id)->first()
                    ];
                    
                    if (env('IS_MAIL_ENABLE', true) == true) {

                        $recevier = Mail::to(mailSendTo($influ_id))->send(new InvitationMail($content));
                    }
                    transactionHistory($invitation->id, $influ_id, '6', ''.$anchor_link.' ', '3', '0', '0', '0', '1', '', $user->id, 'invitations', $invitation->id,$user->currency,NULL);
                }
                $getList = Invitation::where('brand_id', $user->id)->where('camp_id', $request->camp_id)->where('plateform_type',$request->plateform_type)->get();
                return prepareResult(true, 'Invitation send successfully', $getList, $this->success);
            } else{
                return prepareResult(false, "No influencers found", [], $this->not_found);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function InvoiceDownload(Request $request) {
        try {
            $user = getUser();
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',],
                            [
                'order_id.required' => 'Order id is required'
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }

            $saveRequest = new InvoiceDownload;
            $saveRequest->brand_id = $user->id;
            $saveRequest->order_id = $request->order_id;
            $saveRequest->ip_address = \Request::ip();
            $saveRequest->save();
            
            return prepareResult(true, 'save successfully', $saveRequest, $this->success);
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
