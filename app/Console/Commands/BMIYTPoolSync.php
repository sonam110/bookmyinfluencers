<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChannelSearchData;
use App\Models\Channel;
use App\Models\User;
use App\Models\ChannelAssociation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ChannelUpdate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
class BMIYTPoolSync extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BMI:YTPoolSync {sort} {limit}';

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
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
//        DB::enableQueryLog();
        $sort = $this->argument('sort');
        $limit = $this->argument('limit');
        echo Carbon::now() . '------Sync Job Start------' . $sort . PHP_EOL;
        $AllQualifiedChannelSearchData = ChannelSearchData::where('exposure', '>', '100')
                ->where('bmi_app_update_date', '<', Carbon::now()->subDays(7))
                ->orderBy('id', $sort)
                ->limit($limit)
                ->get();
        if (empty($AllQualifiedChannelSearchData) || !is_object($AllQualifiedChannelSearchData)) {
//            ChannelSearchData::update(['bmi_app_update' => 0]);
//            exit(0);
        }
        $counter = 0;
        $influencerRole = Role::where('id', 2)->first();
        foreach ($AllQualifiedChannelSearchData as $aqcsd) {
            $AQSDUpdate = ChannelSearchData::find($aqcsd->id);
            $AQSDUpdate->bmi_app_update = 1;
            $AQSDUpdate->bmi_app_update_date = date('Y-m-d');
            $AQSDUpdate->save();
            $InternalChannel = Channel::where('channel_id', $aqcsd->id)->first();
            if (empty($InternalChannel)) {
                $InternalChannel = new Channel();
                $InternalChannel->uuid = (string) Str::uuid();
                $InternalChannel->plateform = 'youtube';
                $InternalChannel->image = $aqcsd->image_path;
                $InternalChannel->image_path = $aqcsd->image_path;
                $InternalChannel->blur_image = self::blurImage($aqcsd->channel, $aqcsd->image_path);
                $InternalChannel->channel_id = $aqcsd->id;
                echo Carbon::now() . '----' . $aqcsd->channel . '----Inserted.' . PHP_EOL;
            }
            if ($InternalChannel->blur_image == null) {
                $InternalChannel->blur_image = self::blurImage($aqcsd->channel, $aqcsd->image_path);
            }
            /* ----Social data------------ */
            $channelSocial = ChannelUpdate::where('channel', $aqcsd->channel)->first();
            $channelSocialData = (!empty($channelSocial)) ? json_decode($channelSocial->sociallinks) : '';
            $fb = '';
            $tw = '';
            $inst = '';
            if ($channelSocialData != '') {
                foreach ($channelSocialData as $key => $value) {
                    if (str_contains($value, 'facebook')) {
                        $fb = $value;
                    }
                    if (str_contains($value, 'twitter')) {
                        $tw = $value;
                    }
                    if (str_contains($value, 'instagram')) {
                        $inst = $value;
                    }
                }
            }
            $credit_cost = ($aqcsd->credit_cost > 0) ? $aqcsd->credit_cost : '10';
            $InternalChannel->canonical_name = $aqcsd->customurl;
            $InternalChannel->channel = $aqcsd->channel;
            $InternalChannel->channel_link = $aqcsd->customurl ? 'https://www.youtube.com/c/' . $aqcsd->customurl : 'https://www.youtube.com/channel/' . $aqcsd->channel;
            $InternalChannel->name = $aqcsd->name;
            $InternalChannel->number = $aqcsd->cont_number;
            $InternalChannel->email = $aqcsd->conemail;
            $channelLangData = self::getAutoLang($aqcsd->automation_language, $aqcsd->language);
            $InternalChannel->channel_lang = $channelLangData; //!empty($channelLangData) ? $channelLangData : $aqcsd->language;
            $InternalChannel->channel_name = $aqcsd->channel_name;
            $InternalChannel->channel_email = $aqcsd->yt_email;
//            $InternalChannel->categories = $aqcsd->category;
            $InternalChannel->tags = $aqcsd->top_two_tags;
            $InternalChannel->status = '1'; // $aqcsd->status;
            //added extra column to remove dependency on pool data
            $InternalChannel->yt_description = $aqcsd->yt_description;
            $InternalChannel->views = $aqcsd->views;
            $InternalChannel->subscribers = $aqcsd->subscribers;
            $InternalChannel->videos = $aqcsd->videos;
            $InternalChannel->views_sub_ratio = $aqcsd->views_sub_ratio;
            $InternalChannel->engagementrate = $aqcsd->engagementrate;
            $InternalChannel->price_view_ratio = $aqcsd->price_view_ratio;
            $InternalChannel->currency = $aqcsd->currency;
            $InternalChannel->cat_percentile_1 = $aqcsd->cat_percentile_1;
            $InternalChannel->cat_percentile_5 = $aqcsd->cat_percentile_5;
            $InternalChannel->cat_percentile_10 = $aqcsd->cat_percentile_10;
            $InternalChannel->image_path = $aqcsd->image_path;
            $InternalChannel->credit_cost = $credit_cost;
            $InternalChannel->exposure = $aqcsd->exposure;
            $InternalChannel->language = $aqcsd->language;
//            $InternalChannel->category = $aqcsd->category;
            $InternalChannel->tag_category = $aqcsd->tag_category;
            $InternalChannel->facebook = $fb;
            $InternalChannel->twitter = $tw;
            $InternalChannel->instagram = $inst;
            $InternalChannel->inf_recommend = $aqcsd->inf_recommend;
            $InternalChannel->onehit = $aqcsd->onehit;
            $InternalChannel->oldcontent = $aqcsd->oldcontent;
            $InternalChannel->fair_price = $aqcsd->fair_price;
//            $InternalChannel->automation_language = empty($aqcsd->language)$aqcsd->automation_language;
            $InternalChannel->currentdate = date('Y-m-d');
            $InternalChannel->save();

            $ChannelAsso = ChannelAssociation::where('internal_channel_id', $InternalChannel->id)->where('influ_id', 2)->first();
            if (empty($ChannelAsso)) {
                $ChannelAsso = new ChannelAssociation();
            }
            $influ_id ='2';
            if(!empty($aqcsd->yt_email)){
                $checkUser = User::where('email',$aqcsd->yt_email)->first();
                if(empty($checkUser)){
                    $addUser = new User;
                    $addUser->userType = 'influencer';
                    $addUser->fullname = $aqcsd->name;
                    $addUser->phone = $aqcsd->cont_number;
                    $addUser->email = $aqcsd->yt_email;
                    $addUser->password = Hash::make(\Str::random(10));
                    $addUser->ip_address = \Request::ip();
                    $addUser->is_google = 0;
                    $addUser->google_id = null;
                    $addUser->status = '3';
                    $addUser->email_verified_token = \Str::random(36);
                    $addUser->account_type = '0';
                    $addUser->save();
                    $addUser->assignRole($influencerRole);
                    if($addUser){
                        $influ_id = $addUser->id;
                    }
                }
            }
            $ChannelAsso->internal_channel_id = $InternalChannel->id;
            $ChannelAsso->influ_id = $influ_id;
            $ChannelAsso->is_verified = 1;
            $ChannelAsso->yt_key = md5(random_bytes(8));
            $ChannelAsso->type = 0;
            $ChannelAsso->public_profile_url = null;
            $ChannelAsso->promotion_price = $aqcsd->est_price;
            $ChannelAsso->barter = $aqcsd->barter;
            $ChannelAsso->is_default = 0;
            $ChannelAsso->save();
            $counter++;
            echo Carbon::now() . '----' . $ChannelAsso->id . '--CA:ID----.' . PHP_EOL;
            echo Carbon::now() . '----' . $InternalChannel->id . '--INTRNL:ID----Updated.' . PHP_EOL;
            echo Carbon::now() . '----' . $aqcsd->id . '---' . $aqcsd->channel . '--POOL--REF.' . PHP_EOL;
            echo '---------------------------------' . $counter . '------------------------------------.' . PHP_EOL;
        }
        echo Carbon::now() . '------Sync Job End------' . PHP_EOL;
    }

    private static function blurImage($channel, $imagepath) {
        try {
            $file = $imagepath;
            $prImage = $channel . '-blur-img.' . 'jpg';
            $img = \Image::make($file);
            $resource = $img->blur(50)->stream()->detach();
            $storagePath = Storage::disk('s3')->put('channel/blur_img/' . $prImage, $resource, 'public');
            $blur_image = Storage::disk('s3')->url('channel/blur_img/' . $prImage);
            return $blur_image;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function getAutoLang($automation_language, $langaugeCol) {
        $channel_lang_data = '';
        if ($automation_language != '""' && $automation_language != '') {
            $language = json_decode($automation_language, true);
            $i = 0;
            if (!empty($language) && count($language) == 1) {
                foreach ($language as $key => $value) {
                    if (ucfirst($key) != 'English') {
                        $channel_lang_data = ucfirst($key);
                    } else {
                        $lang_count = 0;
                        $language_pw = json_decode($langaugeCol, true);
                        if (!empty($language_pw) && count($language_pw) == 1) {
                            foreach ($language_pw as $key1 => $value1) {
                                if ($lang_count == 0) {
                                    if ($key1 != 'Undefined') {
                                        $channel_lang_data = ucfirst($key1);
                                    }
                                    $lang_count++;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $lang_count = 0;
            $language_pw = json_decode($langaugeCol, true);
            if (!empty($language_pw) && count($language_pw) == 1) {
                foreach ($language_pw as $key1 => $value1) {
                    if ($lang_count == 0) {
                        if ($key1 != 'Undefined') {
                            $channel_lang_data = ucfirst($key1);
                        }
                        $lang_count++;
                    }
                }
            }
        }
        return $channel_lang_data;
    }

}
