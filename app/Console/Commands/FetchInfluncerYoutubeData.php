<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InfluncerChannelSyn;
use App\Models\Channel;
use App\Models\InfluencerYoutubeInfo;
use App\Models\User;
use Exception;
use Alaouy\Youtube\Facades\Youtube;
use DB;
use Illuminate\Support\Carbon;
class FetchInfluncerYoutubeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:youtube-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $influncerChannelSyn = InfluncerChannelSyn::get();
        if (count($influncerChannelSyn) > 0) {
             echo Carbon::now() . '------Sync start------' . PHP_EOL;
            foreach ($influncerChannelSyn as $key => $influ) {
                $channel = \DB::table('channel_association')->select('channels.id','channels.channel_id','channels.canonical_name','channels.channel_name','channels.channel_link','channels.channel_lang','channels.yt_description','channels.channel','channels.image','channels.views','channels.subscribers','channels.videos','channels.facebook','channels.twitter','channels.instagram','channel_association.internal_channel_id','channel_association.influ_id','channel_association.is_default')->join('channels', 'channel_association.internal_channel_id', '=', 'channels.id')->where('channel_association.influ_id', $influ->influ_id)->where('channel_association.is_default', '1')->first();

                if(!empty($channel)){
                    $channeurl = $channel->channel;
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

                    $checkInfluencerYoutubeInfo = InfluencerYoutubeInfo::where('influ_id',$influ->influ_id)->where('internal_channel_id',$channel->internal_channel_id)->first();
                    if(empty($checkInfluencerYoutubeInfo)){
                        $updateRecord = new InfluencerYoutubeInfo;
                    } else{
                        $updateRecord = InfluencerYoutubeInfo::find($checkInfluencerYoutubeInfo->id);
                    }
                        $updateRecord->influ_id = $influ->influ_id;
                        $updateRecord->canonical_name = $influ->canonical_name;
                        $updateRecord->internal_channel_id = $channel->internal_channel_id;
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
                        if($updateRecord){

                            echo Carbon::now() . '----' . $updateRecord->id . '--record:ID----.' . PHP_EOL;
                            InfluncerChannelSyn::where('influ_id',$influ->influ_id)->delete();
                        }



                    }



            }
             echo Carbon::now() . '------Sync Job End------' . PHP_EOL;
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


}
