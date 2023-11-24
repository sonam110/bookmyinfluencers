<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Models\Revealed;
use App\Models\Favourite;
use App\Models\ChannelAssociation;
use Validator;
use Exception;
use DB;
use App\Models\User;

class RevealController extends Controller {

    public function revealedchannels(Request $request) {
        try {
            $user = getUser();
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            if($plateform_type =='2'){
                $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:instagram_data,id'],
                            ['channel_id.required' => 'Channel ID is required',]);
            } else{
                $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:channels,id'],
                            ['channel_id.required' => 'Channel ID is required',]);
            }
            
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $subscriptions = DB::table('subscriptions')
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first();
            if(empty($subscriptions)){
                return prepareResult(false, 'You have not any subscriptions plan please purchase plan ', [], $this->not_found);
            }

            $revealeds = DB::table('revealeds')
                    ->where('channel_id', $request->channel_id)
                    ->where('plateform_type', $plateform_type)
                    ->where('user_id', $user->id)
                    ->first();
            if($plateform_type =='2'){
                $channel = DB::table('instagram_data')->find($request->channel_id);
                $exposure =  $channel->followers;
                $channel_name =  $channel->username;
                $credit_cost = ($channel->credit_cost >0 ) ? $channel->credit_cost :'10';
                $emsg ="Profile already revealed";
                $sucmsg ="Profile revealed successfully";
            } else{

                $channel = DB::table('channels')->find($request->channel_id);
                $exposure =  $channel->exposure;
                $channel_name =  $channel->channel_name;
                $credit_cost = $channel->credit_cost;
                $emsg ="Channel already revealed";
                $sucmsg ="Channel revealed successfully";

            }
            $influInfo = DB::table('channel_association')->where('internal_channel_id',$request->channel_id)->where('is_verified','1')->where('influ_id','!=','2')->first();
            $influ_id = (!empty($influInfo)) ? $influInfo->influ_id:'2';

            if (!empty($revealeds)) {
                return prepareResult(false, $emsg, [], $this->bad_request);
            } else {
//                $channel = ChannelSearchData::select('id', 'credit_cost', 'channel_name')->where('id', $InternalChannel->channel_id)->first();
                if($subscriptions->subscription_plan_id !='3' &&  $exposure >='50000'){
                    return prepareResult(false, 'Please upgrade your plan to view premium influencers', [], $this->unprocessableEntity);
                }
                if ($user->credit_balance < $credit_cost) {
                    return prepareResult(false, 'Your Credit Balance is low, please purchase credits or upgrade your plan.', [], $this->bad_request);
                } else {
                    $reveal = new Revealed;
                    $reveal->user_id = $user->id;
                    $reveal->channel_id = $request->channel_id;
                    $reveal->plateform_type = $plateform_type;
                    $reveal->save();

                    message($reveal->id, $user->id, $influ_id, 'Channel '.$channel_name.' revealed', '0', '1',$request->channel_id,$plateform_type);

                    $UserData = User::find($user->id);
                    $UserData->credit_balance = ($UserData->credit_balance - $credit_cost);
                    $UserData->save();
                    $transaction_id = Str::random(10);

                    transactionHistory($transaction_id, $user->id, '2', 'Channel '.$channel_name.' revealed', '2', $user->credit_balance, $credit_cost, $UserData->credit_balance, '1', '', $user->id, 'revealeds', $reveal->id,$user->currency,NULL);

                    transactionHistory($transaction_id, $influ_id, '14', 'Channel '.$channel_name.' revealed', '3', '0', 0,0, '1', '', $user->id, 'revealeds', $reveal->id,$user->currency,NULL);

                    return prepareResult(true, $sucmsg, $reveal, $this->success);
                }
            }
        } catch(Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function revealedchannelList(Request $request) {
        try {
            $user = getUser();
            $channel_list = [];
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            $revealedsId = DB::table('revealeds')->where('user_id',$user->id)->where('plateform_type',$plateform_type)->pluck('channel_id')->toArray();

            if($plateform_type =='2'){
                $query = DB::table('instagram_data')
                    ->select(array('instagram_data.*','revealeds.id as reveal_id','revealeds.user_id as user_id','instagram_data.id as id'))
                    ->join('revealeds', 'instagram_data.id', '=', 'revealeds.channel_id')
                    ->whereIn('instagram_data.id',$revealedsId)
                    ->where('revealeds.user_id',$user->id)
                    ->orderBy('revealeds.id','DESC');
                $totalCount = count($revealedsId);
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $results = $query;
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $results->count();
                    $channelList = $results->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $channelList = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }
                $channel_list['instachannels'] =  instagramData($channelList,$plateform_type);

            } else{
                $query = DB::table('channels')
                    ->select(['channels.*', 'revealeds.id as reveal_id', 'revealeds.user_id as user_id', 'channels.id as id'])
                    ->join('revealeds', 'channels.id', '=', 'revealeds.channel_id')
                    ->whereIn('channels.id', $revealedsId)
                    ->where('revealeds.user_id',$user->id)
                    ->where('channels.status', '1')
                    ->orderBy('revealeds.id', 'DESC');
                $totalCount = $query->count();
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $results = $query;
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $results->count();
                    $channelList = $results->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $channelList = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }

                $channel_list['revealedchannels'] =   youtubeData($channelList,$plateform_type);
                

            }
            $channel_list['total'] = count($channelList);
            $channel_list['totalCount'] = $totalCount;
            $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
            $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
            $channel_list['last_page'] = $last_page;
            return prepareResult(true, "Revealed Channel list", $channel_list, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function savedchannelList(Request $request) {
        try {
            $user = getUser();
            $channel_list = [];
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            $favouritesId = DB::table('favourites')->where('user_id',$user->id)->where('plateform_type',$plateform_type)->pluck('channel_id')->toArray();


            if($plateform_type =='2'){
                $query = DB::table('instagram_data')
                    ->select(array('instagram_data.*','favourites.id as fav_id','favourites.user_id as user_id','instagram_data.id as id'))
                    ->join('favourites', 'instagram_data.id', '=', 'favourites.channel_id')
                    ->whereIn('instagram_data.id',$favouritesId)
                    ->where('favourites.user_id',$user->id)
                    ->orderBy('favourites.id','DESC');
                $totalCount = $query->count();
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $results = $query;
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $results->count();
                    $channelList = $results->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $channelList = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }
                 $channel_list['savedchannels'] = instagramData($channelList,$plateform_type);

            } else{

                 $query = DB::table('channels')
                    ->select(['channels.*', 'favourites.id as fav_id','favourites.user_id as user_id', 'channels.id as id'])
                    ->join('favourites', 'channels.id', '=', 'favourites.channel_id')
                    ->whereIn('channels.id', $favouritesId)
                    ->where('favourites.user_id',$user->id)
                    ->where('channels.status', '1')
                    ->orderBy('favourites.id', 'DESC');
                $totalCount = $query->count();
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $results = $query;
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $results->count();
                    $channelList = $results->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $channelList = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }
                $channel_list['savedchannels'] =   youtubeData($channelList,$plateform_type);
                
            }
            $channel_list['total'] = count($channelList);
            $channel_list['totalCount'] = $totalCount;
            $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
            $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
            $channel_list['last_page'] = $last_page;
            return prepareResult(true, "Saved Channel listt", $channel_list, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }

    }

}
