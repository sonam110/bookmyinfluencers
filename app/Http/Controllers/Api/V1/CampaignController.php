<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use App\Models\Subscriptions;
use App\Models\Revealed;
use App\Models\Invitation;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use App\Models\User;
use Validator;
use Auth;
use Exception;
use DB;
use Str;
use Illuminate\Support\Facades\Storage;
use Mail;
use App\Mail\InvitationMail;
use App\Mail\ApprovedCampignMail;
use App\Rules\ReferenceVideoRule;
use App\Rules\ReferenceInstaVideoRule;

class CampaignController extends Controller {

    public function campaignList(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
               
                $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
                $query = DB::table('campaigns')->select(array('campaigns.*', DB::raw("(SELECT count(*) from applied_campaigns WHERE applied_campaigns.camp_id = campaigns.id and applied_campaigns.status IN('0','2') ) responseCount"), DB::raw("(SELECT count(*) from applied_campaigns WHERE applied_campaigns.camp_id = campaigns.id and applied_campaigns.status IN('5') ) proposalHistoryCount"), DB::raw("(SELECT count(*) from invitations WHERE invitations.camp_id = campaigns.id  ) inviteCount")))
                            ->where('campaigns.brand_id', $user->id);
                    if ($plateform_type=='2') {
                        $query = $query->where('campaigns.plateform','instagram');
                        $type= 'instagram';
                    
                    }  else{
                        $query =  $query->where('campaigns.plateform','youtube');
                        $type= 'youtube';
                    }
                    $totalC = $query->count();
                   
                    if ($request->status!='') {
                        if($request->status =='4'){
                            $query = $query->whereIn('campaigns.status',['4','5']);
                        } else{
                            $query = $query->where('campaigns.status',''.$request->status.'');

                        }
                    
                    } 
                    $campaignCount = DB::table('campaigns')->select(DB::raw('COUNT(IF(status = "3", 0, NULL)) as draftCount'),
                    DB::raw('COUNT(IF(status != "3", 0, NULL)) as totalCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as InvitedCount'),
                    DB::raw('COUNT(IF(status = "1", 0, NULL)) as liveCount'),
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as pendingCount'),
                    DB::raw('COUNT(IF(status IN ("4","5"), 0, NULL)) as rejectCount'),
                    )->where('brand_id', $user->id)->where('plateform',$type)->first();

                    if(!empty($request->perPage))
                    {
                        $perPage = $request->perPage;
                        $page = $request->input('page', 1);
                        $total = $query->count();
                        $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->orderBy('campaigns.id', 'ASC')->get();
                       
                        $pagination =  [
                            'campaign' => $result,
                            'drafts' => [],
                            'totalCount' => @$totalC,
                            'draftCount' => @$campaignCount->draftCount,
                            'InvitedCount' => @$campaignCount->InvitedCount,
                            'allCampaignsCount' => @$campaignCount->allCampaignsCount,
                            'liveCount' => @$campaignCount->liveCount,
                            'pendingCount' => @$campaignCount->pendingCount,
                            'rejectCount' => @$campaignCount->rejectCount,
                            'total' => $total,
                            'current_page' => $page,
                            'per_page' => $perPage,
                            'status' => $request->status,
                            'last_page' => ceil($total / $perPage),
                        ];
                        $query = $pagination;
                    }
                    else
                    {
                        $query =  [
                            'campaign' => $query->orderBy('campaigns.id', 'ASC')->get(),
                            'drafts' => $drafts,
                            'totalCount' => @$totalC,
                            'draftCount' => @$campaignCount->draftCount,
                            'InvitedCount' => @$campaignCount->InvitedCount,
                            'allCampaignsCount' => @$campaignCount->allCampaignsCount,
                            'liveCount' => @$campaignCount->liveCount,
                            'pendingCount' => @$campaignCount->pendingCount,
                            'rejectCount' => @$campaignCount->rejectCount,
                            'status' => $request->status,
                        ];
                    }
     

                return prepareResult(true, "Campaign list", $query, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function addCampaign(Request $request) {
        try {
            $user = getUser();

            if ($user->roles[0]->name == 'brand') {
                if ($request->type == 'draft') {
                    $validator = Validator::make($request->all(), [
                                'camp_title' => 'required',
                                    ],
                                    [
                                    'camp_title.required' => 'Campaign Title  is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                } else {
                    $validator = Validator::make($request->all(), [
                                'plateform' => 'required',
                                'brand_name' => 'required',
                                'brand_url' => 'required',
                                //'brand_logo' => 'required',
                                'camp_title' => 'required',
                                'camp_desc' => 'required',
                               // 'subscriber' => 'numeric',
                                //'average_view' => 'numeric',
                                'budget' => 'numeric',
                                    ],
                                    [
                                        'plateform.required' => 'plateform  is required',
                                        'brand_name.required' => 'Brand Name Link  is required',
                                        'brand_url.required' => 'Brand url required',
                                        'camp_title.required' => 'Campaign Title  is required',
                                        'camp_desc.required' => 'Campaign description  is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                    if ($request->type != 'draft') {

                        $checkTitle = Campaign::where('brand_id',$user->id)->where('camp_title',$request->camp_title)->where('plateform',$request->plateform)->first();
                        if(!empty($checkTitle)){
                            return prepareResult(false, 'Campaign title must be unique', [], $this->unprocessableEntity);
                        }
                    }
                    
                }

                if ($request->reference_videos) {
                    if($request->plateform=='youtube'){
                        $validator = Validator::make($request->all(), [
                            'reference_videos' => [new ReferenceVideoRule],
                        ]);

                    } else{
                        $validator = Validator::make($request->all(), [
                            'reference_videos' => [new ReferenceInstaVideoRule],
                        ]);

                    }
                    
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }

                $name = Str::slug($request->camp_title);
                if (!empty($request->brand_logo)) {
                    $file = $request->brand_logo;
                    $image_parts = explode(";base64,", $file);
                   
                    $image_type_aux = explode("image/", @$image_parts[0]);
                    $image_type = @$image_type_aux[1];
                    $image_base64 = base64_decode(@$image_parts[1]);
                    $prImage = time() . '-' . $name . '.' . $image_type;
                    $storagePath = Storage::disk('s3')->put('brand/logo/' . $prImage, $image_base64, 'public');
                    $brand_logo = Storage::disk('s3')->url('brand/logo/' . $prImage);
                   
                } else {
                    $brand_logo = '';
                }
               
                $status = '0';
                $responseMessage = 'Campaign Created successfully';
                $plan = Subscriptions::where('user_id', $user->id)->where('status', 1)->with('plan')->first();
                $approval_required = '0';
                $invite_Onlyss = ($request->invite_only == '1') ? 1 : 0;
                $visibility = ($request->visibility == '1') ? 1 : 0;
                if ($plan->plan->price == 0) {
                    $invite_Onlyss = 0;
                    $visibility = 0;
                }
                $approval_required = ($plan->plan->campaign_approval == '1') ? '1' : '0';
                if ($approval_required == '1') {
                    $status = '1';
                    $responseMessage = 'Your Campaign is live';
                }
                if ($request->type == 'draft') {
                    $status = '3';
                    $responseMessage = 'Your Campaign is saved as draft';
                } elseif ($approval_required == '0') {
                    $responseMessage = 'Your campaign is now awaiting review.Average wait time: '.$plan->plan->campaign_approval_hrs.'';
                    $status = '0';
                } 
                $ExistingCampaign = Campaign::where('brand_id', $user->id)->whereNotIn('status',['3','4','5'])->count();
                $userCampaignPermission = ($plan->plan) ? $plan->plan->campaigns :'1';
                if($request->type !='draft') {
                    if ($ExistingCampaign > 0 && $plan->plan->price == 0) {
                        return prepareResult(false, "Please upgrade to create more than one live campaign, please upgrade.", [], $this->unprocessableEntity);
                    }
                    
                    if ($ExistingCampaign >= $userCampaignPermission ) {
                        return prepareResult(false, "Your active plan is limited to ".$userCampaignPermission." live campaigns", [], $this->unprocessableEntity);
                    }
                }

                if ($request->plateform=='youtube') {
                    $plateform_type ='1';
                
                }  else{
                    $plateform_type ='2';
                }
                $campaign = new Campaign;
                $campaign->brand_id = $user->id;
                $campaign->plateform = $request->plateform;
                $campaign->plateform_type = $plateform_type;
                $campaign->brand_name = ($request->brand_name) ? $request->brand_name : $user->fullname;
                $campaign->brand_url = $request->brand_url;
                $campaign->brand_logo = $brand_logo;
                $campaign->camp_title = $request->camp_title;
                $campaign->camp_desc = $request->camp_desc;
                $campaign->duration = $request->duration;
                $campaign->promotion_start = $request->promotion_start;
                $campaign->promot_product = $request->promot_product;
                $campaign->script_approval = ($request->script_approval) ? 1 : 0;
                $campaign->reference_videos = $request->reference_videos;
                $campaign->category = $request->category;
                $campaign->subscriber = $request->subscriber;
                $campaign->average_view = $request->average_view;
                $campaign->followers = $request->followers;
                $campaign->inf_score = $request->inf_score;
                $campaign->budget = $request->budget;
                $campaign->currency = ($request->currency) ? $request->currency : auth()->user()->currency;
                $campaign->engagement_rate = ($request->engagement_rate) ? $request->engagement_rate : 0;
                $campaign->lang = $request->lang;
                $campaign->status = $status;
                $campaign->invite_only = $invite_Onlyss;
                $campaign->visibility = $visibility;
                $campaign->save();
                transactionHistory($campaign->id, $user->id, '3', 'New Campaign posted', '3', '0', '0', '0', '1', 'New Campaign posted', $user->id, 'campaigns', $campaign->id,$request->currency,NULL);
                $campaignList = Campaign::where('id', $campaign->id)->first();

               /* if($status =='1'){
                    $idsArr = explode(',', $request->category);
                    $query = Channel::where('status', '1');
                    $first = 1;
                    foreach ($idsArr as $term) {
                        if ($first) {
                            $query->Where('channels.tags', 'LIKE', "%{$term}%");
                        } else {
                            $query->orWhere('channels.tags', 'LIKE', "%{$term}%");
                        }
                        $first = 0;
                    }
                    $channelList = $query->limit(10)->get();
                    foreach ($channelList as $key => $channel) {
                        $channel_association = ChannelAssociation::where('is_verified', 1)->where('internal_channel_id', $channel->id)->first();
                        $influ_id = (!empty($channel_association)) ? $channel_association->influ_id : '2';
                        $promotion_price = (!empty($channel_association)) ? $channel_association->promotion_price : '0';
                        $invitation = Invitation::where('channel_id',$channel)->where('camp_id', $campaign->id)->where('brand_id', $user->id)->first();
                        $channel_d = Channel::where('id', $channel)->first();
                        if($campaign->budget >= $promotion_price) {
                            if (empty($invitation)) {
                                $invitation = new Invitation;
                            }
                            $invitation->brand_id = $user->id;
                            $invitation->camp_id = $campaign->id;
                            $invitation->influ_id = $influ_id;
                            $invitation->channel_id = $channel->id;
                            $invitation->save();
                            $userInfo = User::find($influ_id);
                            $email = $userInfo->email;
                            if(!empty(@$channel_d->email)){
                                $email = $email;
                            }
                           
                            $baseRedirURL = env('APP_URL');
                            $link = $baseRedirURL."/campaign-detail/" . $campaign->uuid . "/" . base64url_encode($email) . "";
                            $content = [
                                "user" => User::where('id', $influ_id,)->first(),
                                "email" => $email,
                                "link" => $link,
                                "channel_name" => @$channel_d->title,
                                "campaign" => Campaign::where('id', $campaign->id)->first()
                            ];

                            if (env('IS_MAIL_ENABLE', true) == true) {
                                $recevier = Mail::to($email)->send(new InvitationMail($content));
                            }
                        }
                   
                    }

                }*/
                
                if ($status == 0) {
                    
                    $content = [
                        'brand_name' => $campaign->brand_name,
                        'uuid' => $campaign->uuid,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        $recevier = Mail::to(env('MANAGER_MAIL', 'abhishek@prchitects.com'))->send(new ApprovedCampignMail($content));
                    }
                }
                /*if($status == '1') {
                    $content = [
                        'userId' => $user->id,
                        'camp_id' => $campaign->id,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        $recevier = Mail::to(env('MANAGER_MAIL', 'abhishek@prchitects.com'))->send(new ApprovedCampignMail($content));
                    }
                }*/
               
                return prepareResult(true, $responseMessage, $campaignList, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function editCampaign(Request $request) {

        try {
            $user = getUser();
            $id = $request->id;
            if ($user->roles[0]->name == 'brand') {
                if ($request->type == 'draft') {
                    $validator = Validator::make($request->all(), [
                                'camp_title' => 'required',
                
                                    ],
                                    [
                                        'camp_title.required' => 'Campaign Title  is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                } else {
                    $validator = Validator::make($request->all(), [
                                'id' => 'required',
                                'plateform' => 'required',
                                'brand_name' => 'required',
                                'brand_url' => 'required',
                                'camp_title' => 'required',
                                'camp_desc' => 'required',
                                //'subscriber' => 'numeric',
                                //'average_view' => 'numeric',
                                'budget' => 'numeric',
                                    ],
                                    [
                                        'id.required' => 'id  is required',
                                        'plateform.required' => 'plateform  is required',
                                        'brand_name.required' => 'Brand Name Link  is required',
                                        'brand_url.required' => 'Brand url required',
                                        'camp_title.required' => 'Campaign Title  is required',
                                        'camp_desc.required' => 'Campaign description  is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                    if ($request->type != 'draft') {
                        $checkTitle = Campaign::where('id','!=',$id)->where('brand_id',$user->id)->where('camp_title',$request->camp_title)->where('plateform',$request->plateform)->first();
                        if(!empty($checkTitle)){
                            return prepareResult(false, 'Campaign title must be unique', [], $this->unprocessableEntity);
                        }
                    }
                    
                }

                
                $checkEmp = Campaign::where('brand_id', $user->id)->where('id', $id)->first();
                if (!is_object($checkEmp)) {
                    return prepareResult(false, "Campaign Id Not Found", [], $this->not_found);
                }
                
                if ($request->reference_videos) {
                    if($request->plateform=='youtube'){
                        $validator = Validator::make($request->all(), [
                            'reference_videos' => [new ReferenceVideoRule],
                        ]);

                    } else{
                        $validator = Validator::make($request->all(), [
                            'reference_videos' => [new ReferenceInstaVideoRule],
                        ]);

                    }
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                $logo = Campaign::find($id);
                $brand_logo = $logo->brand_logo;
                $name = Str::slug($request->camp_title);
                if (!empty($request->brand_logo)){
                    $file = $request->brand_logo;
                    $image_parts = explode(";base64,", $file);
                    $image_type_aux = explode("image/", @$image_parts[0]);
                    if(@$image_type_aux[1]){
                        $image_type = @$image_type_aux[1];
                        $image_base64 = base64_decode(@$image_parts[1]);
                        $prImage = time() . '-' . $name . '.' . $image_type;
                        $storagePath = Storage::disk('s3')->put('brand/logo/' . $prImage, $image_base64, 'public');
                        $brand_logo = Storage::disk('s3')->url('brand/logo/' . $prImage);

                    } else{
                        $brand_logo = $logo->brand_logo;
                    }
                }
                $status = '0';
                $plan = Subscriptions::where('user_id', $user->id)->where('status', 1)->with('plan')->first();
                $invite_Onlyss = ($request->invite_only) ? 1 : 0;
                $visibility = ($request->visibility) ? 1 : 0;
                if ($plan->plan->price == 0) {
                    $invite_Onlyss = 0;
                    $visibility = 0;
                }
                $responseMessage = 'Campaign updated successfully';
                $approval_required = ($plan->plan->campaign_approval == '1') ? '1' : '0';
                if ($approval_required == '1' ) {
                    $status = '1';
                    $responseMessage = 'Campaign updated successfully';
                }
                if ($request->type == 'draft') {
                    $status = '3';
                    $responseMessage = 'Your Campaign is saved as draft';
                } elseif ($approval_required == '0' && $checkEmp->status !='1') {
                    $responseMessage = 'Your campaign is now awaiting review.Average wait time: '.$plan->plan->campaign_approval_hrs.'';
                    $status = '0';
                }
                if($checkEmp->status =='1'){
                    $status = '1';
                    $responseMessage = 'Campaign updated successfully';
                }
                $ExistingCampaign = Campaign::where('brand_id', $user->id)->whereNotIn('status',['3','4','5'])->count();
                $userCampaignPermission = ($plan->plan) ? $plan->plan->campaigns :'1';
                if($request->type !='draft' && $checkEmp->status !='1') {
                    if ($ExistingCampaign > 0 && $plan->plan->price == 0) {
                        return prepareResult(false, "Please upgrade to create more than one live campaign..", [], $this->unprocessableEntity);
                    }
                
                    if ($ExistingCampaign >= $userCampaignPermission ) {
                        return prepareResult(false, "Your active plan is limited to ".$userCampaignPermission." live campaigns", [], $this->unprocessableEntity);
                    }
                }
                if ($request->plateform=='youtube') {
                    $plateform_type ='1';
                
                }  else{
                    $plateform_type ='2';
                }

                $campaign = Campaign::find($id);
                $campaign->brand_id = $user->id;
                $campaign->plateform = $request->plateform;
                $campaign->plateform_type = $plateform_type;
                $campaign->brand_name = $request->brand_name;
                $campaign->brand_url = $request->brand_url;
                $campaign->brand_logo = $brand_logo;
                $campaign->camp_title = $request->camp_title;
                $campaign->camp_desc = $request->camp_desc;
                $campaign->duration = $request->duration;
                $campaign->promotion_start = $request->promotion_start;
                $campaign->promot_product = $request->promot_product;
                $campaign->script_approval = ($request->script_approval) ? 1 : 0;
                $campaign->reference_videos = $request->reference_videos;
                $campaign->category = $request->category;
                $campaign->subscriber = $request->subscriber;
                $campaign->average_view = $request->average_view;
                $campaign->followers = $request->followers;
                $campaign->inf_score = $request->inf_score;
                $campaign->budget = $request->budget;
                $campaign->currency = ($request->currency) ? $request->currency : auth()->user()->currency;
                $campaign->engagement_rate = $request->engagement_rate;
                $campaign->lang = $request->lang;
                $campaign->invite_only = $invite_Onlyss;
                $campaign->visibility = $visibility;
                $campaign->status = $status;
                $campaign->save();
                $campaignList = Campaign::where('id', $id)->first();
                return prepareResult(true,$responseMessage, $campaignList, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function viewCampaign(Request $request) {

        $id = $request->id;
        $getCampaign = Campaign::where('id', $id)->where('brand_id', Auth::id())->first();
        if ($getCampaign) {
            return prepareResult(true, 'Campaign view', $getCampaign, $this->success);
        } else {
            return prepareResult(true, 'No Campaign Found', [], $this->not_found);
        }
    }

    public function deleteCampaign(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->id;

                $checkEmp = Campaign::where('brand_id', $user->id)->where('id', $id)->first();
                if (!is_object($checkEmp)) {
                    return prepareResult(false, "Id Not Found", [], $this->not_found);
                }

                $CampaignDelete = Campaign::where('id', $id)->update(['status'=>'5']);
                return prepareResult(true, 'Campaign Deleted', [], $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

   

    public function publicCampaignList(Request $request) {
        try {
            $query = DB::table('campaigns')->where('campaigns.status', '1')
                    ->random();
            $totalCount = $query->count(); 
            if(!empty($request->perPage))
            {
                $perPage = $request->perPage;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination =  [
                    'campaign' => $result,
                    'liveCount' => $totalCount,
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
                    'liveCount' => $totalCount,
                ];
            }

            return prepareResult(true, "Public Campaign list", $query, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
