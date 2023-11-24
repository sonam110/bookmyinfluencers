<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppliedCampaign;
use Validator;
use Auth;
use Exception;
use DB;

class ApplicationController extends Controller {

    public function getApplication(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {
                $limit = 10;
                $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
                if($plateform_type =='2'){
                    $query = AppliedCampaign::select(array('applied_campaigns.*','campaigns.*','instagram_data.username','applied_campaigns.id as id','instagram_data.id as channelid','campaigns.id as camp_id','applied_campaigns.status as status','instagram_data.status as channel_status','campaigns.status as camp_status','applied_campaigns.created_at as created_at','campaigns.created_at as camp_created_at','instagram_data.currency as instagram_data_currency','campaigns.currency as camp_currency','applied_campaigns.currency as currency'))->leftjoin('instagram_data','instagram_data.id','applied_campaigns.channel_id')->leftjoin('campaigns','campaigns.id','applied_campaigns.camp_id')->where('applied_campaigns.influ_id',$user->id)->orderby('applied_campaigns.id','DESC');

                } else{
                    $query = AppliedCampaign::select(array('applied_campaigns.*','campaigns.*','channels.channel_name','applied_campaigns.id as id','channels.id as channelid','campaigns.id as camp_id','applied_campaigns.status as status','channels.status as channel_status','campaigns.status as camp_status','applied_campaigns.created_at as created_at','channels.created_at as channel_created_at','campaigns.created_at as camp_created_at','channels.currency as channels_currency','campaigns.currency as camp_currency','applied_campaigns.currency as currency'))->leftjoin('channels','channels.id','applied_campaigns.channel_id')->leftjoin('campaigns','campaigns.id','applied_campaigns.camp_id')->where('applied_campaigns.influ_id',$user->id)->orderby('applied_campaigns.id','DESC');

                }
                $query->where('applied_campaigns.plateform_type',$plateform_type)->orderby('applied_campaigns.id','DESC');
                $totalCount = $query->whereIn('applied_campaigns.status',['0','2'])->count();
                if ($request->status!='') {
                    $query->where('applied_campaigns.status',''.$request->status.'');
                
                } else{
                    $query->whereIn('applied_campaigns.status',['0','2']);
                }
                
               
               
                $proposalCount =  DB::table('applied_campaigns')->select(
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as pendingCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as shortlistCount'),
                    DB::raw('COUNT(IF(status = "3", 0, NULL)) as rejectedCount'),
                    DB::raw('COUNT(IF(status = "5", 0, NULL)) as completedCount'),
                    )->where('influ_id', $user->id)->where('applied_campaigns.plateform_type',$plateform_type)
                ->first();

                if(!empty($request->perPage))
                {
                    $perPage = $request->perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                    $pagination =  [
                        'applications' => $result,
                        'totalCount' => @$totalCount,
                        'pendingCount' => @$proposalCount->pendingCount,
                        'shortlistCount' => @$proposalCount->shortlistCount,
                        'rejectedCount' => @$proposalCount->rejectedCount,
                        'completedCount' =>@$proposalCount->completedCount,
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
                        'applications' => $query->whereIn('applied_campaigns.status',['0','2'])->get(),
                        'totalCount' => @$totalCount,
                        'pendingCount' => @$proposalCount->pendingCount,
                        'shortlistCount' => @$proposalCount->shortlistCount,
                        'rejectedCount' => @$proposalCount->rejectedCount,
                        'completedCount' =>@$proposalCount->completedCount,
                    ];
                }
                return prepareResult(true, 'Application List', $query, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function ViewApplication(Request $request) {
        $user = getUser();
        $id = $request->id;
        $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
        if($plateform_type =='2'){
            $getApplication = AppliedCampaign::select(array('applied_campaigns.*','campaigns.*','applied_campaigns.id as id','applied_campaigns.price as price','instagram_data.id as channelid','campaigns.id as camp_id','applied_campaigns.status as status','instagram_data.username as title','instagram_data.bio as description','instagram_data.status as channel_status','campaigns.status as camp_status','applied_campaigns.created_at as created_at','campaigns.created_at as camp_created_at','instagram_data.currency as instagram_data_currency','campaigns.currency as camp_currency','applied_campaigns.currency as currency','applied_campaigns.currency as currency','applied_campaigns.comment as comment'))->leftjoin('instagram_data','instagram_data.id','applied_campaigns.channel_id')->leftjoin('campaigns','campaigns.id','applied_campaigns.camp_id')->where('applied_campaigns.id',$id)->where('applied_campaigns.influ_id',$user->id)->orderby('applied_campaigns.id','DESC');

            } else{
                $getApplication = AppliedCampaign::select(array('applied_campaigns.*','campaigns.*','applied_campaigns.id as id','applied_campaigns.price as price','channels.id as channelid','channels.channel_name as title','channels.yt_description as description','campaigns.id as camp_id','applied_campaigns.status as status','channels.status as channel_status','campaigns.status as camp_status','applied_campaigns.created_at as created_at','channels.created_at as channel_created_at','campaigns.created_at as camp_created_at','channels.currency as channels_currency','campaigns.currency as camp_currency','applied_campaigns.currency as currency','applied_campaigns.comment as comment'))->leftjoin('channels','channels.id','applied_campaigns.channel_id')->leftjoin('campaigns','campaigns.id','applied_campaigns.camp_id')->where('applied_campaigns.id',$id)->where('applied_campaigns.influ_id',$user->id)->orderby('applied_campaigns.id','DESC');

            }
           $getApplication = $getApplication->where('applied_campaigns.plateform_type',$plateform_type)->first();;
        if ($getApplication) {
            return prepareResult(true, 'Application view', $getApplication, $this->success);
        } else {
            return prepareResult(true, 'No Campaign Found', [], $this->not_found);
        }
    }
    public function deleteApplication(Request $request) {

        try {
            $user = getUser();

            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->id;

                $checkApp = DB::table('applied_campaigns')->where('influ_id', $user->id)->where('id', $id)->first();
                if (!is_object($checkApp)) {
                    return prepareResult(false, "Id Not Found", [], $this->not_found);
                }
                if ($checkApp->status == 1 || $checkApp->status == 2 || $checkApp->status == 5 ) {
                    return prepareResult(false, "You can't delete this proposal, your proposal has been accepted by brand.", [], $this->unprocessableEntity);
                } else {
                    $proposalDelete = AppliedCampaign::where('id', $id)->delete();
                    return prepareResult(true, 'Proposal Deleted', [], $this->success);
                }
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

    private function getWhereRawFromRequest(Request $request) {
        $w = '';
        if (is_null($request->input('status')) == false) {
            if ($w != '') {
                $w = $w . " AND ";
            }
            $w = $w . "(" . "status = " . "'" . $request->input('status') . "'" . ")";
        }
        return($w);
    }

}
