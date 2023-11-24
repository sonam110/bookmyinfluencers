<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use Exception;
use Validator;
use DB;
use App\Models\CreateList;
use App\Models\listing;
use AmrShawky\LaravelCurrency\Facade\Currency;
use App\Models\Revealed;
use App\Models\User;
class NoMiddlewareController extends Controller {


    public function unauthorized(Request $request)
    {
       return prepareResult(false,'Unauthorized. Please login.', [], $this->unauthorized);
    }

    public function channelList(Request $request) {
        try {
            $channel_list['channel_list'] = [];
            $query = DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','blur_image','tag_category','facebook','twitter','instagram','inf_recommend','price_view_ratio','channel_id','tags','channel')->where('channel_id', '!=', '')
                    ->where('status', '1');
            $search_keyword = $request->search_keyword;
            //$idsArr = explode(',', $search_keyword);
            if (isset($search_keyword) && !empty($search_keyword)) {
                $idsArr = explode(',', $search_keyword);
                $first = 1;
                foreach ($idsArr as $term) {
                    if ($first) {
                        $query->Where('channels.tag_category', 'LIKE', "%{$term}%");
                    } else {
                        $query->orWhere('channels.tag_category', 'LIKE', "%{$term}%");
                    }
                    $first = 0;
                }
                
                $query->where('tag_category', 'LIKE', "%{$search_keyword}%")->orWhere('channel_name', 'LIKE', "%{$search_keyword}%")->orWhere('yt_description', 'LIKE', "%{$search_keyword}%");
            } else {
                $query = $query;
            }
            $channelList = $query->limit(1)->get();

            foreach ($channelList as $key => $channel) {

                $channel_association = DB::table('channel_association')->select('id','internal_channel_id','promotion_price','influ_id','is_default','is_verified')->where('internal_channel_id', $channel->id)->first();
                $influ_id = (!empty($channel_association)) ? $channel_association->influ_id : '2';
                $vidoe_promotion_price = (!empty($channel_association)) ? $channel_association->promotion_price : 0;
                    $is_favourite = false;

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
                    $credit_cost = ($channel && isset($channel->credit_cost)) ? $channel->credit_cost : 0;
                    $channel_link = "https://www.youtube.com/channel/".$channel->channel."";
                    $channel_list['channel_list'][] = [
                        "id" => $channel->id,
                        "influ_id" => $influ_id,
                        "channel_id" => $channel->channel_id,
                        "channel" => $channel->channel,
                        "is_default" => (!empty($channel_association)) ? $channel_association->is_default : false,
                        "is_verified" => (!empty($channel_association)) ? $channel_association->is_verified : '0',
                        "channel_link" => $channel_link,
                        "promotion_price" => $vidoe_promotion_price,
                        "title" => $title,
                        "description" => $description,
                        "viewCount" => $viewCount,
                        "subscriberCount" => $subscriberCount,
                        "videoCount" => $videoCount,
                        "ViewsSubs" => $ViewsSubs,
                        "EngagementRate" => $EngagementRate,
                        "EstViews" => $EstViews,
                        "cpm" => $ViewsPrice,
                        "tag_category" => $channel->tag_category,
                        "tags" => tags($channel->tags),
                        "cat_percentile_1" => $channel->cat_percentile_1,
                        "cat_percentile_5" => $channel->cat_percentile_5,
                        "cat_percentile_10" => $channel->cat_percentile_10,
                        "profile_pic" => $channel->image_path,
                        "blur_image" => $channel->blur_image,
                        "credit_cost" => $credit_cost,
                    ];
                }
            

            $channel_list['totalCount'] = count($channelList);
            return prepareResult(true, "Channel list", $channel_list, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }
    public function showSimilar(Request $request) {
        try {
            $channel_list['channel_list'] = [];
            
            $search_keyword = $request->search_keyword;
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            if($plateform_type =='2'){
                $query = DB::table('instagram_data')->select('*');
                $totalCount = $query->count();

                if (isset($search_keyword) && !empty($search_keyword)) {
                    $idsArr = explode(',', $search_keyword);
                    $first = 1;
                    foreach ($idsArr as $term) {
                        if ($first) {
                            $query->Where('instagram_data.tag_category', 'LIKE', "%{$term}%");
                        } else {
                            $query->orWhere('instagram_data.tag_category', 'LIKE', "%{$term}%");
                        }
                        $first = 0;
                    }
                }

                if (is_null($request->followers) == false) {
                    $query->where('followers', '>', $request->followers);
                }
                if (is_null($request->average_view) == false) {
                    $query->where('following', '>', $request->following);
                }
                if (is_null($request->inf_score) == false) {
                    $query->where('inf_score', '>', $request->inf_score);
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
                $name =  'channel_list';
                $channel_list['channel_list'] = instagramData($channelList,$plateform_type);

            } else{
                $query =  DB::table('channels')->where('channels.channel_id', '!=', '')
                ->where('channels.status', '1')
                ->inRandomOrder()->orderBy('channels.exposure');
                if (isset($search_keyword) && !empty($search_keyword)) {
                    $query->where(function ($q) use ($search_keyword) {
                        $q->where('channels.tag_category', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.channel_name', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.canonical_name', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.name', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.yt_description', 'LIKE', "%{$search_keyword}%");
                    });
                        
                       
                }
                if (is_null($request->subscriber) == false) {
                    $query->where('channels.subscribers', '>', $request->subscriber);
                }
                if (is_null($request->average_view) == false) {
                    $query->where('channels.exposure', '>', $request->average_view);
                }
                if (is_null($request->engagement_rate) == false) {
                    $query->where('channels.engagementrate', '>', $request->engagement_rate);
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
            
                $channel_list['channel_list'] =   youtubeData($channelList,$plateform_type);

            }

            $channel_list['totalCount'] = count($channelList);
            $channel_list['total'] = count($channelList);
            $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
            $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
            $channel_list['last_page'] = $last_page;

            return prepareResult(true, "Channel list", $channel_list, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function channelAutoSuggest(Request $request) {
        try {
            $channel_list['channel_list'] = [];
            $search_keyword = $request->search_keyword;
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            if($plateform_type =='2'){
               
                $query = DB::table('instagram_data')->select('*');
                if (isset($search_keyword) && !empty($search_keyword)) {
                   /* $idsArr = explode(',', $search_keyword);
                    $first = 1;
                    foreach ($idsArr as $term) {
                        if ($first) {
                            $query->Where('channels.tag_category', 'LIKE', "%{$term}%");
                        } else {
                            $query->orWhere('channels.tag_category', 'LIKE', "%{$term}%");
                        }
                        $first = 0;
                    }
                    */
                    $query->where('username', 'LIKE', "%{$search_keyword}%")->orWhere('name', 'LIKE', "%{$search_keyword}%");
                } else {
                    $query = $query;
                }
                $perPage = (!empty($request->perPage)) ? $request->perPage:20 ;   
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
                
               
                foreach ($channelList as $key => $channel) {

                    //$title = (!empty($channel->canonical_name)) ? $channel->canonical_name : '';
                    if(!empty($channel->username)){
                        $channel_list['channel_list'][] = [
                            "title" => $channel->username,
                
                        ];

                    }
                }
            } else{

                $query = DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','blur_image','tag_category','facebook','twitter','instagram','inf_recommend','price_view_ratio','channel_id','canonical_name')->where('channel_id', '!=', '')
                        ->where('canonical_name','!=', '');
              
                if (isset($search_keyword) && !empty($search_keyword)) {
                   /* $idsArr = explode(',', $search_keyword);
                    $first = 1;
                    foreach ($idsArr as $term) {
                        if ($first) {
                            $query->Where('channels.tag_category', 'LIKE', "%{$term}%");
                        } else {
                            $query->orWhere('channels.tag_category', 'LIKE', "%{$term}%");
                        }
                        $first = 0;
                    }
                    */
                    $query->where('channel_name', 'LIKE', "%{$search_keyword}%")->orWhere('canonical_name', 'LIKE', "%{$search_keyword}%");
                } else {
                    $query = $query;
                }
                $perPage = (!empty($request->perPage)) ? $request->perPage:20 ;   
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

                foreach ($channelList as $key => $channel) {

                    //$title = (!empty($channel->canonical_name)) ? $channel->canonical_name : '';
                    if(!empty($channel->canonical_name)){
                        $channel_list['channel_list'][] = [
                            "title" => $channel->canonical_name,
                
                        ];

                    }
                }
            }
            
            $channel_list['total'] = count($channelList);
            $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
            $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
            $channel_list['last_page'] = $last_page;
            
            return prepareResult(true, "Channel list", $channel_list, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function currencyConvert(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'from' => 'required|in:USD,INR',
                'to' => 'required|in:USD,INR',
                'amount' => 'required|numeric',
                ],
                [
                'from.required' => 'From Currency  is required',
                'to.required' => 'To Currency',
                'amount.required' => 'Amount Field is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
            $data = [];
            $currency =    Currency::convert()
                ->from($request->from)
                ->to($request->to)
                ->amount($request->amount)
                ->round(2)
                ->get();
            $curr = (!empty($currency)) ? $currency: '';
            $data = [
                "amount" =>$curr,

            ];
            
            return prepareResult(true, "currency convert", $data, $this->success);

        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function channelListAdd(Request $request) {
        try {
            $user = getUser();
            $channel_list['channel_list'] = [];
            $search_keyword = $request->search_keyword;
            $is_reveal_or_not = $request->is_reveal_or_not;
            $required_credit = 0;
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            $revealedChannel = DB::table('revealeds')->where('user_id',$user->id)->where('plateform_type',$request->plateform_type)->pluck('channel_id');
            if($plateform_type =='2'){
                if($is_reveal_or_not == '1'){
                    $query = DB::table('instagram_data')->select('*')->whereIn('id',$revealedChannel);
                } else{
                    $query = DB::table('instagram_data')->select('*')->whereNotIn('id',$revealedChannel);
                }
                
                if ($request->followers!='' ) {
                    $query->where('followers', '<', $request->followers);
                }
                if ($request->following!='') {
                    $query->where('following', '<', $request->following);
                }
                if ($request->inf_score!='') {
                    $query->where('inf_score', '<', $request->inf_score);
                }

                if (isset($search_keyword) && !empty($search_keyword)) {
                    $idsArr = explode(',', $search_keyword);
                    $first = 1;
                    foreach ($idsArr as $term) {
                        if ($first) {
                            $query->Where('instagram_data.tag_category', 'LIKE', "%{$term}%");
                        } else {
                            $query->orWhere('instagram_data.tag_category', 'LIKE', "%{$term}%");
                        }
                        $first = 0;
                    }
                    if($is_reveal_or_not == '2'){
                    
                        $query->where('category', 'LIKE', "%{$search_keyword}%")->orWhere('username', 'LIKE', "%{$search_keyword}%");
                    }
                }
                $perPage = (!empty($request->perPage)) ? $request->perPage:20 ;   
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
                
               
                foreach ($channelList as $key => $channel) {
                    $credit_cost = (!empty($channel->credit_cost)) ? $channel->credit_cost: '10';
                    $required_credit += $credit_cost;
                    //$title = (!empty($channel->canonical_name)) ? $channel->canonical_name : '';
                    if(!empty($channel->username)){
                        $channel_list['channel_list'][] = [
                            "title" =>  ($is_reveal_or_not == '1')? $channel->username: 'xxxxxx',
                            "channel_name" => ($is_reveal_or_not == '1') ? $channel->username: 'xxxxxx',
                            "credit_cost" => $credit_cost,
                            "id" => $channel->id,
                
                        ];

                    }
                }
            } else{
                if($is_reveal_or_not == '1'){
                    $query = DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','blur_image','tag_category','facebook','twitter','instagram','inf_recommend','price_view_ratio','channel_id','canonical_name')->where('channel_id', '!=', '')
                        ->whereIn('id',$revealedChannel)
                        ->where('canonical_name','!=', '');
                } else{
                    $query = DB::table('channels')->select('id','channel_name','yt_description','views','subscribers','videos','views_sub_ratio','views_sub_ratio','engagementrate','exposure','credit_cost','channel','channel_lang','channel_link','currency','cat_percentile_1','cat_percentile_5','cat_percentile_10','image_path','email','blur_image','tag_category','facebook','twitter','instagram','inf_recommend','price_view_ratio','channel_id','canonical_name')->where('channel_id', '!=', '')
                        ->whereNotIn('id',$revealedChannel)
                        ->where('canonical_name','!=', '');

                }
                    if ($request->subscriber!='') {
                        $query->where('channels.subscribers', '<', $request->subscriber);
                    }
                    if ($request->average_view!='') {
                        $query->where('channels.exposure', '<', $request->average_view);
                    }
                    if ($request->engagement_rate!='') {
                        if ($request->engagement_rate == "5") {

                            $query->where('channels.engagementrate', '<', "5");
                        }
                        if ($request->engagement_rate == "5-10") {

                            $query->whereBetween('channels.engagementrate', ["5", "10"]);
                        }
                        if ($request->engagement_rate == "10") {

                            $query->where('channels.engagementrate', '>', "10");
                        }
                    }

                    if (isset($search_keyword) && !empty($search_keyword)) {
                        $idsArr = explode(',', $search_keyword);
                        $first = 1;
                        foreach ($idsArr as $term) {
                            if ($first) {
                                $query->Where('channels.tag_category', 'LIKE', "%{$term}%");
                            } else {
                                $query->orWhere('channels.tag_category', 'LIKE', "%{$term}%");
                            }
                            $first = 0;
                        }
                        if($is_reveal_or_not == '2'){
                            $query->where('channels.tag_category', 'LIKE', "%{$search_keyword}%")->orWhere('channels.channel_name', 'LIKE', "%{$search_keyword}%")->orWhere('channels.yt_description', 'LIKE', "%{$search_keyword}%");

                         
                        }
                        
                       //$query->where('channels.channel_name', 'LIKE', "%{$search_keyword}%");
                    }

                    
                    if (is_null($request->language) == false) {
                        $query->where('channels.language', $request->language);
                    }


                $perPage = (!empty($request->perPage)) ? $request->perPage:20 ;   
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

                foreach ($channelList as $key => $channel) {
                    $credit_cost = (!empty($channel->credit_cost)) ? $channel->credit_cost: '10';
                    $required_credit += $credit_cost;

                    //$title = (!empty($channel->canonical_name)) ? $channel->canonical_name : '';
                    if(!empty($channel->canonical_name)){
                        $channel_list['channel_list'][] = [
                            "title" => ($is_reveal_or_not == '1')? $channel->canonical_name:'XXXXXX',
                            "channel_name" => ($is_reveal_or_not == '1')? $channel->canonical_name:'XXXXXX',
                            "credit_cost" => $credit_cost,
                            "id" => $channel->id,
                
                        ];

                    }
                }
            }
            
            $channel_list['total'] = count($channelList);
            $channel_list['user_credit'] = auth()->user()->credit_balance;
            $channel_list['required_credit'] = ($is_reveal_or_not == '2') ? $required_credit :0;
            $channel_list['low_balance'] = ($is_reveal_or_not == '2' && $required_credit > auth()->user()->credit_balance) ? true:false ;
            $channel_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
            $channel_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
            $channel_list['last_page'] = $last_page;
            
            return prepareResult(true, "Channel list", $channel_list, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function addProfileToList(Request $request) {
        try {
            $user = getUser();
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            $validator = Validator::make($request->all(), [
                'list_id' => 'required_without:list_name',
                'list_name' => 'required_without:list_id',
                'channel_ids' => 'required',
                'plateform_type' => 'required|in:1,2',

            ]);
           
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }

            if($request->is_reveal_or_not =='2'){
                if($request->required_credit > auth()->user()->credit_balance){
                   return prepareResult(false, 'Your credit balance is low', [], $this->unprocessableEntity);
                }
            }
            $list = DB::table('create_lists')->where('brand_id', auth()->user()->id)->where('id',$request->list_id)->first();
            $transaction_id = \Str::random(10);   

             
            $list_id = $request->list_id;
            if(empty($request->list_id)){
                $list = new CreateList;
                $list->brand_id = $user->id;
                $list->name = $request->list_name;
                $list->plateform_type = $plateform_type;
                $list->save();
                $list_id = $list->id;

            }
            $channels = explode(',',$request->channel_ids);
            foreach ($channels as $key => $channel) {
                $checkIsAl = listing::where('brand_id',$user->id)->where('list_id',$list_id)->where('channel_id',$channel)->first();
                if($plateform_type =='2'){
                    $channeldetail = \DB::table('instagram_data')->where('id',$channel)->first();
                    $channel_name =  $channeldetail->username;
                  
                } else{
                    $channeldetail = \DB::table('channels')->where('id',$channel)->first();
                    $channel_name =  $channeldetail->channel_name;
                 
                }
                
                $influInfo = \DB::table('channel_association')->where('internal_channel_id',$channel)->where('plateform_type',$plateform_type)->where('is_verified','1')->where('influ_id','!=','2')->first();

                $influ_id = (!empty($influInfo)) ? $influInfo->influ_id:'2'; 
                if(empty($checkIsAl)){
                    $listing = new listing;
                    $listing->brand_id = $user->id;
                    $listing->list_id = $list_id;
                    $listing->channel_id = $channel;
                    $listing->plateform_type = $plateform_type;
                    $listing->status = '1';
                    $listing->save();

                    transactionHistory($transaction_id, $influ_id, '13', 'Added to '.$list->name.'', '3', '0',0,0, '1', '', $user->id, 'listings', $listing->id,$user->currency,NULL);

                   

                }
                if($request->is_reveal_or_not =='2'){
                    $reveal = new Revealed;
                    $reveal->user_id = $user->id;
                    $reveal->channel_id = $channel;
                    $reveal->plateform_type = $plateform_type;
                    $reveal->save();

                
                    message($reveal->id, $user->id, $influ_id, 'Channel '.$channel_name.' revealed', '0', '1',$channel,$plateform_type);

                    transactionHistory($transaction_id, $influ_id, '14', 'Channel '.$channel_name.' revealed', '3', '0', 0,0, '1', '', $user->id, 'revealeds', $reveal->id,$user->currency,NULL);
                }

               
            }

            if($request->is_reveal_or_not =='2'){
            
                $UserData = User::find($user->id);
                $UserData->credit_balance = ($UserData->credit_balance - $request->required_credit);
                $UserData->save();
                $transaction_id = \Str::random(10);
                
                transactionHistory($transaction_id, $user->id, '2', 'Revealed '.$request->list_size.' profiles [Added to '.$list->name.']', '2', $user->credit_balance, $request->required_credit, $UserData->credit_balance, '1', '', $user->id, 'revealeds', $reveal->id,$user->currency,NULL);

            }
            return prepareResult(true, 'Profiles added successfully',$list, $this->success);
        } catch(Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
