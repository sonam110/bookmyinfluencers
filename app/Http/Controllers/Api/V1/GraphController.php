<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Channel;
use Exception;
use DB;
class GraphController extends Controller {

    public function influencersGraph(Request $request) {
        try {
            $user = getUser();
            $channel_list = [];
            $totalInfluencer = 0;
            $premiumInfluencer = 0;
            $nonPremiumInfluencer = 0;
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            if (is_null($request->average_view) == false) {
                $average_view = $request->average_view;
            } else {
                $average_view = '50000';
            }
            if ($user->roles[0]->name == 'brand') {
                if($plateform_type =='1'){
                    $query = DB::table('channels')->select('id','tags','tag_category','exposure','subscribers','engagementrate','channel_name','canonical_name','name','yt_description');

                    $search_keyword = $request->search_keyword;

                    if (isset($search_keyword) && !empty($search_keyword)) {
                        $query->where(function ($q) use ($search_keyword) {
                            $q->where('channels.tag_category', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.channel_name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.canonical_name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.name', 'LIKE', "%{$search_keyword}%")
                            ->orWhere('channels.yt_description', 'LIKE', "%{$search_keyword}%");
                        });
                    }
                    if ($request->average_view!='') {
                        $query->where('exposure', '>', $request->average_view);
                    }
                    if ($request->subscriber !='') {
                        $query->where('subscribers', '>', $request->subscriber);
                    }
                    if ($request->engagement_rate !='') {
                        $query->where('engagementrate', '>', $request->engagement_rate);
                    }
                    $totalInfluencer = $query->count();
                    $premiumInfluencer = $query->where('exposure','>','50000')->count();
                    $nonPremiumInfluencer = $totalInfluencer - $premiumInfluencer ;
                    
                    $channel_list = [
                        "totalInfluencer" => $totalInfluencer,
                        "premiumInfluencer" => $premiumInfluencer,
                        "nonPremiumInfluencer" => $nonPremiumInfluencer,
                    ];

                }
                if($plateform_type =='2'){
                    $query = DB::table('instagram_data')->select('id','category','tag_category','followers','inf_score','name','username');

                    $search_keyword = $request->search_keyword;

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

                    //return $query->toSql();
                    if ($request->followers !='') {
                        $query->where('followers', '>', $request->followers);
                    }
                    if ($request->inf_score !='') {
                        $query->where('inf_score', '>', $request->inf_score);
                    }
                    
                    $totalInfluencer = $query->count();
                    $premiumInfluencer = $query->where('followers','>','50000')->count();
                    $nonPremiumInfluencer = $totalInfluencer - $premiumInfluencer ;
                    
                    $channel_list = [
                        "totalInfluencer" => $totalInfluencer,
                        "premiumInfluencer" => $premiumInfluencer,
                        "nonPremiumInfluencer" => $nonPremiumInfluencer,
                    ];

                }

                return prepareResult(true, "Channel Graph Data", $channel_list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    private function getWhereRawFromRequest(Request $request) {
        $w = '';
        $p = '';

        if (is_null($request->input('subscriber')) == false) {
            if ($w != '') {
                $w = $w . " AND ";
            }
            $w = $w . "(" . "subscribers >= " . "'" . $request->input('subscriber') . "'" . ")";
        }
        if (is_null($request->input('engagement_rate')) == false) {
            if ($w != '') {
                $w = $w . " AND ";
            }
            $w = $w . "(" . "engagementrate >= " . "'" . $request->input('engagement_rate') . "'" . ")";
        }
        if (is_null($request->input('average_view')) == false) {
            if ($p != '') {
                $p = $p . " AND ";
            }
            $p = $p . "(" . "avgviews >= " . "'" . $request->input('average_view') . "'" . ")";
        }
        $response = [
            'w' => $w,
            'p' => $p,
        ];
        return $response;
    }

}
