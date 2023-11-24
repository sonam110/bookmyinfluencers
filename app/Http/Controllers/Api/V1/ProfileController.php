<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelAssociation;
use App\Models\ChannelUpdate;
use App\Models\InfluencerYoutubeInfo;
use App\Models\User;
use Exception;
use Alaouy\Youtube\Facades\Youtube;
use DB;
class ProfileController extends Controller {

    public function publicProfile($channel,$uuid) {

        try {
            $user = DB::table('users')->where('uuid',$uuid)->first();
            if (empty($user)) {
                return prepareResult(false, "User not found", [], $this->not_found);
            }
            $list = DB::table('channel_association')->select('channels.id','channels.channel_id','channels.canonical_name','channels.channel_name','channels.channel_link','channels.channel_lang','channels.yt_description','channels.channel','channels.image','channels.views','channels.subscribers','channels.videos','channels.facebook','channels.twitter','channels.instagram','channel_association.internal_channel_id','channel_association.id','channel_association.influ_id')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')
                ->where('channel_association.influ_id', $user->id)
                ->where('channel_association.is_default','1')
                ->where('channels.canonical_name', $channel)->first();
            if (empty($list)) {
                return prepareResult(false, "Profile not found", [], $this->not_found);
            }
          
            $youtubeData = InfluencerYoutubeInfo::where('influ_id',$user->id)->where('internal_channel_id',$list->internal_channel_id)->first();
            $fb = $list->facebook;
            $tw = $list->twitter;
            $inst = $list->instagram;
            if(!empty($youtubeData)){
                $list = DB::table('channels')->select('id','facebook','twitter','instagram','channel_link','channel','channel_id')->where('id',$youtubeData->internal_channel_id)->first();
               
                $data = [
                    "channel_id" => $list->channel_id,
                    "channel" => $list->channel,
                    "channel_link" => $list->channel_link,
                    "title" => $youtubeData->title,
                    "description" => $youtubeData->description,
                    "profile_pic" => $youtubeData->profile_pic,
                    "viewCount" => $youtubeData->viewCount,
                    "subscriberCount" => $youtubeData->subscriberCount,
                    "videoCount" => $youtubeData->videoCount,
                    "averageView" => $youtubeData->averageView,
                    "mostViewsVideos" => json_decode($youtubeData->mostViewsVideos,true),
                    "lastetViewsVideos" => json_decode($youtubeData->lastetViewsVideos,true),
                    "featuredVideo" =>json_decode($youtubeData->featuredVideo,true),
                    "facebook" => $fb,
                    "twitter" => $tw,
                    "instagram" => $inst,
                    "last_10_views" => $youtubeData->last_10_views,
                    "last_20_views" => $youtubeData->last_20_views,
                    "last_30_views" => $youtubeData->last_30_views,
                    "latest_5_views" => $youtubeData->latest_5_views,
                    "latest_10_views" => $youtubeData->latest_10_views,
                    "latest_30_views" => $youtubeData->latest_30_views,
                ];

            } else{
                    $channeurl = $list->channel;
                    $getVideos = @$this->getLastestVideoNum($channeurl);
                    $getLastVideosCount = @$this->getLastVideoNum($channeurl);
                    $mostViewsVideos = @$this->mostViewsVideos($channeurl);
                    $lastetViewsVideos = @$this->lastetViewsVideos($channeurl);
                    $featuredVideo = @$this->lastetViewsVideos($channeurl)[0];

                    $activities = Youtube::getChannelById($channeurl);

                    $title = ($activities && isset($activities->snippet->title)) ? $activities->snippet->title : '';
                    $description = ($activities && isset($activities->snippet->description)) ? $activities->snippet->description : '';
                    $viewCount = ($activities && isset($activities->statistics->viewCount)) ? $activities->statistics->viewCount : '';
                    $subscriberCount = ($activities && isset($activities->statistics->subscriberCount)) ? $activities->statistics->subscriberCount : '';
                    $videoCount = ($activities && isset($activities->statistics->videoCount)) ? $activities->statistics->videoCount : '';
                    $profile_pic = ($activities && isset($activities->snippet->thumbnails->high)) ? $activities->snippet->thumbnails->high->url : '';

                    $EstViews = round(@$getVideos['view_count'] / 5, 0);

                    $checkInfluencerYoutubeInfo = InfluencerYoutubeInfo::where('influ_id',$user->id)->where('internal_channel_id',$list->internal_channel_id)->first();

                    if(empty($checkInfluencerYoutubeInfo)){
                        $updateRecord = new InfluencerYoutubeInfo;
                    } else{
                        $updateRecord = InfluencerYoutubeInfo::find($checkInfluencerYoutubeInfo->id);
                    }
                        $updateRecord->influ_id = $user->id;
                        $updateRecord->canonical_name = $list->canonical_name;
                        $updateRecord->internal_channel_id = $list->internal_channel_id;
                        $updateRecord->title = $title;
                        $updateRecord->description = $description;
                        $updateRecord->profile_pic = $profile_pic;
                        $updateRecord->viewCount = $viewCount;
                        $updateRecord->subscriberCount = $subscriberCount;
                        $updateRecord->videoCount = $videoCount;
                        $updateRecord->averageView = $EstViews;
                        $updateRecord->lastetViewsVideos = json_encode($lastetViewsVideos);
                        $updateRecord->mostViewsVideos = json_encode($mostViewsVideos);
                        $updateRecord->featuredVideo = json_encode($featuredVideo);
                        $updateRecord->last_10_views = @$getLastVideosCount['last_10_views'];
                        $updateRecord->last_20_views = @$getLastVideosCount['last_20_views'];
                        $updateRecord->last_30_views = @$getLastVideosCount['last_30_views'];
                        $updateRecord->latest_5_views = @$getVideos['latest_5_views'];
                        $updateRecord->latest_10_views = @$getVideos['latest_10_views'];
                        $updateRecord->latest_30_views = @$getVideos['latest_30_views'];
                        $updateRecord->save();

                        $data = [
                            "channel_id" => $list->channel_id,
                            "channel" => $list->channel,
                            "channel_link" => $list->channel_link,
                            "title" => $updateRecord->title,
                            "description" => $updateRecord->description,
                            "profile_pic" => $updateRecord->profile_pic,
                            "viewCount" => $updateRecord->viewCount,
                            "subscriberCount" => $updateRecord->subscriberCount,
                            "videoCount" => $updateRecord->videoCount,
                            "averageView" => $updateRecord->averageView,
                            "mostViewsVideos" => json_decode($updateRecord->mostViewsVideos,true),
                            "lastetViewsVideos" => json_decode($updateRecord->lastetViewsVideos,true),
                            "featuredVideo" =>json_decode($updateRecord->featuredVideo,true),
                            "facebook" => $fb,
                            "twitter" => $tw,
                            "instagram" => $inst,
                            "last_10_views" => $updateRecord->last_10_views,
                            "last_20_views" => $updateRecord->last_20_views,
                            "last_30_views" => $updateRecord->last_30_views,
                            "latest_5_views" => $updateRecord->latest_5_views,
                            "latest_10_views" => $updateRecord->latest_10_views,
                            "latest_30_views" => $updateRecord->latest_30_views,
                        ];

            }
            

            return prepareResult(true, 'profile', $data, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

   
    function getLastVideoNum($internal_channel_id) {
        $LatestvideoList = Youtube::searchChannelVideos('', $internal_channel_id,'30', 'viewCount');
        $resultArr = [];
        $view_count = 0;
        $last_10_views =0;
        $last_20_views =0;
        $last_30_views =0;
        foreach ($LatestvideoList as $key => $video) {
            if($key= '9'){
                $allvideos = Youtube::getVideoInfo($video->id->videoId);
                $last_10_views += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
                
            }
            if($key= '19'){
                $allvideos = Youtube::getVideoInfo($video->id->videoId);
                $last_20_views += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
            }
            if($key= '29'){
                $allvideos = Youtube::getVideoInfo($video->id->videoId);
                $last_30_views += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
            }
            
        }

           
        $resultArr =[
            'last_10_views'=>$last_10_views,
            'last_20_views'=>$last_20_views,
            'last_30_views'=>$last_30_views,
           

        ];

        return $resultArr;
    }

    function getLastestVideoNum($internal_channel_id) {
        $LatestvideoList = Youtube::searchChannelVideos('', $internal_channel_id,'30', 'date');
        $view_count = 0;
        $latest_5_views = 0;
        $latest_10_views = 0;
        $latest_30_views = 0;

        foreach ($LatestvideoList as $key => $video) {
            
            if($key= '4'){
                $allvideos = Youtube::getVideoInfo($video->id->videoId);
                $latest_5_views += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
                $view_count += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
            }
            if($key= '9'){
                $allvideos = Youtube::getVideoInfo($video->id->videoId);
                $latest_10_views += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
            }
            if($key= '29'){
                $allvideos = Youtube::getVideoInfo($video->id->videoId);
                $latest_30_views += (!empty($allvideos->statistics)) ? @$allvideos->statistics->viewCount : 0;
            }
            
        }

        $resultArr =[
            'latest_5_views'=>$latest_5_views,
            'latest_10_views'=>$latest_10_views,
            'latest_30_views'=>$latest_30_views,
            'view_count'=> $view_count,
           

        ];
        return $resultArr;
    }

    function mostViewsVideos($internal_channel_id) {
        $mostViewsVideos = Youtube::listChannelVideos($internal_channel_id, '1', 'viewCount');
        $viewVideoArray = [];
        $media_url = '';
        $videoId = '';
        $title = '';
        $description = '';
        $publishedAt = '';
        foreach ($mostViewsVideos as $key => $video) {
            $title = (isset($video->snippet)) ? $video->snippet->title : '';
            $description = (isset($video->snippet)) ? $video->snippet->description : '';
            $publishedAt = (isset($video->snippet)) ? $video->snippet->publishedAt : '';
            $videoId = (isset($video->id)) ? $video->id->videoId : '';
            $media_url = (isset($video->snippet->thumbnails)) ? $video->snippet->thumbnails->high->url : '';
            $viewVideoArray[] = [
                "title" => $title,
                "description" => $description,
                "publishedAt" => date('Y-m-d', strtotime($publishedAt)),
                "videoId" => $videoId,
                "media_url" => $media_url,
                "internal_channel_id" => $internal_channel_id,
            ];
        }
        return $viewVideoArray;
    }
     function getLatestFiveVideo($internal_channel_id) {
        $LatestvideoList = Youtube::searchChannelVideos('', $internal_channel_id, 5, 'date');

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

    function lastetViewsVideos($internal_channel_id) {
        $mostViewsVideos = Youtube::listChannelVideos($internal_channel_id, '10', 'date');
        $viewVideoArray = [];
        $media_url = '';
        $videoId = '';
        $title = '';
        $description = '';
        $featureVideo = [];
        foreach ($mostViewsVideos as $key => $video) {
           
            $title = (isset($video->snippet)) ? $video->snippet->title : '';
            $videoId = (isset($video->id)) ? $video->id->videoId : '';
            $media_url = (isset($video->snippet->thumbnails)) ? $video->snippet->thumbnails->high->url : '';
            $viewVideoArray[] = [
                "title" => $title,
                "videoId" => $videoId,
                "media_url" => $media_url,
                "internal_channel_id" => $internal_channel_id,
            ];
        }
       
        return $viewVideoArray;
    }

    function featuredVideo($internal_channel_id) {
        $mostViewsVideos = Youtube::listChannelVideos($internal_channel_id, '1', 'date');
        $viewVideoArray = [];
        $media_url = '';
        $videoId = '';
        $title = '';
        $description = '';
        foreach ($mostViewsVideos as $key => $video) {
            $title = (isset($video->snippet)) ? $video->snippet->title : '';
            $videoId = (isset($video->id)) ? $video->id->videoId : '';
            $media_url = (isset($video->snippet->thumbnails)) ? $video->snippet->thumbnails->high->url : '';
            $viewVideoArray[] = [
                "title" => $title,
                "videoId" => $videoId,
                "media_url" => $media_url,
                "internal_channel_id" => $internal_channel_id,
            ];
        }

        return $viewVideoArray;
    }

    private function getWhereRawFromRequest($channel) {
        $w = '';
        if (is_null($channel) == false) {
            if (strlen($channel) == '24') {
                if ($w != '') {
                    $w = $w . " AND ";
                }
                $w = $w . "(" . "channel = " . "'" . $channel . "'" . ")";
            } else {
                if ($w != '') {
                    $w = $w . " AND ";
                }
                $w = $w . "(" . "canonical_name = " . "'" . $channel . "'" . ")";
            }
        }

        return($w);
    }
    public function getYoutubeList($keyword,$maxResult,$channelId,$order){
        
            $apikey = 'AIzaSyDFjLtOIbLzlIJcf9rBtknqfa7Tpti1V74'; 

            $googleApiUrl ='https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=' . $channelId . '&key=' . $apikey.'&maxResults=' . $maxResult . '&order=' . $order . '';
           
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            curl_close($ch);
            $data = json_decode($response);
            $value = json_decode(json_encode($data), true);
          
            $viewVideoArray = [];
            $media_url = '';
            $videoId = '';
            $title = '';
            $description = '';
            for ($i = 0; $i < $maxResult; $i++) {
                $videoId = @$value['items'][$i]['id']['videoId'];
                $title = @$value['items'][$i]['snippet']['title'];
                $description = @$value['items'][$i]['snippet']['description'];
                $media_url = @$value['items'][$i]['snippet']['thumbnails']['high']['url'];
                $viewVideoArray[] = [
                    "title" => $title,
                    "description" => $description,
                    "videoId" => $videoId,
                    "media_url" => $media_url,
                ];
            

            }
            return $viewVideoArray;
        
    }
    public function getYoutubeListStatics($keyword,$maxResult,$channelId,$order){
        
            $apikey = 'AIzaSyDFjLtOIbLzlIJcf9rBtknqfa7Tpti1V74'; 

            $googleApiUrl ='https://www.googleapis.com/youtube/v3/channels?part=statistics&id=' . $channelId . '&key=' . $apikey.'&maxResults=' . $maxResult . '&order=' . $order . '';
           
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            curl_close($ch);
            $data = json_decode($response);
            $value = json_decode(json_encode($data), true);

            $resultArr = [];
            $viewCount = 0;
            $last_10_views =0;
            $last_20_views =0;
            $last_30_views =0;
            for ($i = 0; $i <=$maxResult; $i++) {
                $viewCount += @$value['items'][$i]['statistics']['viewCount'];

                if($i= '9'){
                    $last_10_views += @$value['items'][$i]['statistics']['viewCount'];
                    
                }
                if($i= '19'){
                    $last_20_views += @$value['items'][$i]['statistics']['viewCount'] ;
                }
                if($i= '29'){
                    $last_30_views += @$value['items'][$i]['statistics']['viewCount'];
                }

            }
             $resultArr =[
                'last_10_views'=>$last_10_views,
                'last_20_views'=>$last_20_views,
                'last_30_views'=>$last_30_views,
               

            ];

            return $resultArr;
        
    }

}
