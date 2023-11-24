<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invitation;
//use App\Models\AppliedCampaign;
//use App\Models\Order;
//use App\Models\Campaign;
use App\Models\Channel;
use App\Models\ChannelAssociation;
//use App\Models\ChannelSearchData;
//use App\Models\ChannelEngagement;
//use App\Models\ChannelUpdate;
//use App\Models\ChannelViews;
use App\Models\Favourite;
use App\Models\Revealed;
//use Validator;
//use Auth;
//use DB;
use Exception;
use DB;
class SearchController extends Controller {

    public function channelList(Request $request) {
        try {
            $user = getUser();
            $channel_list['channel_list'] = [];
            $search_keyword = $request->search_keyword;
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';

            if ($user->roles[0]->name == 'brand') {
                if($plateform_type =='2'){

                    $query = DB::table('instagram_data')
                    ->where('instagram_data.status', '!=','4')
                    ->orderby('instagram_data.followers','DESC');

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

                        $query->where(function ($q) use ($search_keyword) {
                            $q->where('instagram_data.name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('instagram_data.category', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('instagram_data.username', 'LIKE', "%{$search_keyword}%");
                        });
                        
                        //$query->where('category', 'LIKE', "%{$search_keyword}%")->orWhere('username', 'LIKE', "%{$search_keyword}%");
                    }
                    

                    if (is_null($request->followers) == false) {
                        $query->where('instagram_data.followers', '>', $request->followers);
                    }
                    if (is_null($request->following) == false) {
                        $query->where('instagram_data.following', '>', $request->following);
                    }
                    if (is_null($request->inf_score) == false) {
                        $query->where('instagram_data.inf_score', '>', $request->inf_score);
                    }

                    

                    if ($request->accept_free_promotion == true) {
                        //$query->where('instagram_data.subscribers', '<', $request->subscriber);
                    }
                    if ($request->premium_influencers_only == true) {
                        $query->where('instagram_data.followers','>','50000');
                    }
                    if ($request->must_have_promo_price == true) {
                        $query->whereNotNull('instagram_data.fair_price')->where('instagram_data.fair_price','<>', 0);
                    }
                     if ($request->exclude_low_quality_influencers == true) {
                        $query->where('instagram_data.profile_post','0');
                    }

                    $totalCount = $query->count();

                    $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                    if(!empty($perPage))
                    {
                        $perPage = $perPage;
                        $page = $request->input('page', 1);
                        $total = $query->count();
                        $channelList = $query->offset(($page - 1) * $perPage)->limit($perPage)->groupby('instagram_data.id')->get();
                        $last_page = ceil($total / $perPage);
                       
                       
                    }
                    else
                    {
                        $channelList = $query->groupby('instagram_data.id')->get();
                        $total = $query->count();
                        $last_page = ceil($total / 10);
                    }
                   
                    $name =  'channel_list';
                    $channel_list['channel_list'] = instagramData($channelList,$plateform_type);

                } else {
                    $query = DB::table('channels')
                    ->where('channels.status', '1')
                    ->orderby('channels.exposure','DESC');
                   
                    /*if (isset($search_keyword) && !empty($search_keyword)) {
                        $query->where('tag_category', 'LIKE', "%{$search_keyword}%")->orWhere('channel_name', 'LIKE', "%{$search_keyword}%")->orWhere('yt_description', 'LIKE', "%{$search_keyword}%")->orWhereRaw("find_in_set('".$search_keyword."',tags)");
                       // $query->where('tag_category', 'LIKE', "%{$search_keyword}%")->orWhere('channel_name', 'LIKE', "%{$search_keyword}%")->orWhere('yt_description', 'LIKE', "%{$search_keyword}%");
                    }*/


                    if (isset($search_keyword) && !empty($search_keyword)) {
                        $idsArr = explode(',', $search_keyword);
                        $first = 1;
                        /*foreach ($idsArr as $term) {
                            if ($first) {
                                $query->where('channels.tag_category', 'LIKE', "%{$term}%");
                            } else {
                                $query->orWhere('channels.tag_category', 'LIKE', "%{$term}%");
                            }
                            $first = 0;
                        }*/
                        $query->where(function ($q) use ($search_keyword) {
                            $q->where('channels.tag_category', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.channel_name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.canonical_name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.yt_description', 'LIKE', "%{$search_keyword}%");
                        });
                        
                       //$query->where('channels.tag_category', 'LIKE', "%{$search_keyword}%")->orWhere('channels.channel_name', 'LIKE', "%{$search_keyword}%")->orWhere('channels.yt_description', 'LIKE', "%{$search_keyword}%");
                    }

                    if (is_null($request->subscriber) == false) {
                        $query->where('channels.subscribers', '>', $request->subscriber);
                    }
                    if (is_null($request->average_view) == false) {
                        $query->where('channels.exposure', '>', $request->average_view);
                    }
                    if (is_null($request->engagement_rate) == false) {
                        if ($request->engagement_rate == "5") {

                            $query->where('channels.engagementrate', '>', "5");
                        }
                        if ($request->engagement_rate == "5-10") {

                            $query->whereBetween('channels.engagementrate', ["5", "10"]);
                        }
                        if ($request->engagement_rate == "10") {

                            $query->where('channels.engagementrate', '>', "10");
                        }
                    }

                    
                    if (is_null($request->language) == false) {
                        $query->where('channels.language', $request->language);
                    }
                    if ($request->accept_free_promotion == true) {
                        //$query->where('channels.subscribers', '<', $request->subscriber);
                    }
                    if ($request->premium_influencers_only == true) {
                        $query->where('channels.exposure','>','50000');
                    }
                    if ($request->must_have_promo_price == true) {
                        $query->whereNotNull('channels.fair_price')->where('channels.fair_price','<>', 0);
                    }
                     if ($request->exclude_low_quality_influencers == true) {
                        $query->where('channels.oldcontent','0')->orWhere('channels.onehit','1')->orWhere('channels.videos','0');
                    }
                   // $query = $query->toSql();
                    //return $query;

                    $totalCount = $query->count();
                    $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                    if(!empty($perPage))
                    {
                        $perPage = $perPage;
                        $page = $request->input('page', 1);
                        $total = $query->count();
                        $channelList = $query->offset(($page - 1) * $perPage)->limit($perPage)->groupby('channels.id')->get();
                        $last_page = ceil($total / $perPage);
                       
                       
                    }
                    else
                    {
                        $channelList = $query->groupby('channels.id')->get();
                        $total = $query->count();
                        $last_page = ceil($total / 10);
                    }

                    $channel_list['channel_list'] =   youtubeData($channelList,$plateform_type);
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
//            dd($exception);
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function getinternal_channel_ids() {
        $getSearchData = DB::table('channels')->where('channel_id', '!=', '')->where('status', '1')->orderby('channel_id', 'ASC')->get();
        $channel_ids = [];
        foreach ($getSearchData as $key => $value) {
            $channel_ids[] = $value->channel_id;
        }
        return $channel_ids;
    }

    public function recommendedInfluencers(Request $request) {
        try {

            $user = getUser();
            $search_keyword = (isset($user->category_preferences) && $user->category_preferences != '') ? $user->category_preferences : null;
            //$idsArr = explode(',', $userCategory);
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            $revealedChannel = DB::table('revealeds')->where('user_id',$user->id)->where('plateform_type',$plateform_type)->pluck('channel_id');
            $channel_list['channel_list'] = [];
            if ($user->roles[0]->name == 'brand') {
                if($plateform_type =='2'){

                    $query = DB::table('instagram_data')->select('*')->where('status', '!=','4')->whereNotIn('id',$revealedChannel);
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
                        
                       /* $query->where(function ($q) use ($search_keyword) {
                            $q->where('instagram_data.tag_category', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('instagram_data.category', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('instagram_data.name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('instagram_data.username', 'LIKE', "%{$search_keyword}%");
                        });*/
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
                    $channel_list['totalCount'] = $totalCount;
                    $channel_list['channel_list'] =   instagramData($channelList,$plateform_type);

                } else {
                $query = DB::table('channels')
                ->where('channels.status', '1')
                ->where('channels.subscribers','>=','50000')
                ->whereNotNull('channels.fair_price')
                ->where('channels.fair_price','<>', 0)
                ->whereNotIn('channels.id',$revealedChannel);
                
                if (isset($search_keyword) && !empty($search_keyword)) {
                    $searchCategories = explode(',', $search_keyword);
                    $query->where(function ($q) use ($searchCategories) {
                        foreach ($searchCategories as $category) {
                            $q->orWhere('channels.tag_category', 'like', '%' . $category . '%');
                        }
                    });

                    /*$first = 1;
                    foreach ($idsArr as $term) {
                        if ($first) {
                            $query->Where('channels.tag_category', 'LIKE', "%{$term}%");
                        } else {
                            $query->orWhere('channels.tag_category', 'LIKE', "%{$term}%");
                        }
                        $first = 0;
                    }*/
                    /*$query->where(function ($q) use ($search_keyword) {
                        $q->where('channels.tag_category', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.channel_name', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.name', 'LIKE', "%{$search_keyword}%")
                        ->orWhere('channels.yt_description', 'LIKE', "%{$search_keyword}%");
                    });*/
                    
                  
                };

              // return $query->toSql();


               
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $channelList = $query->offset(($page - 1) * $perPage)->limit($perPage)->groupby('channels.id')->orderby('exposure','DESC')->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $channelList = $query->groupby('channels.id')->orderby('exposure','DESC')->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }
                $channel_list['channel_list'] =   youtubeData($channelList,$plateform_type);

                $channel_list['totalCount'] = DB::table('channels')->where('channel_id', '!=', '')
                        ->inRandomOrder()
                        ->where('status', '1')
                        ->count();
                }

                $channel_list['total'] = count($channelList);
                $channel_list['totalCount'] = count($channelList);
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

}
