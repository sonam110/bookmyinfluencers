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
use App\Models\InstagramData;
use App\Models\InstagramDataBmi;
class BMIIGPoolSync extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BMI:IGPoolSync {sort} {limit}';

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
        $AllQualifiedInstagramData = InstagramData::whereIn('status', [1,2,3])
                ->where('app_update_date', '<', Carbon::now()->subDays(7))
                ->orderBy('id', $sort)
                ->limit($limit)
                ->get();
        $counter = 0;
        // dd($AllQualifiedInstagramData);
        foreach ($AllQualifiedInstagramData as $aqcsd) {
            $AQSDUpdate = InstagramData::find($aqcsd->id);
            $AQSDUpdate->app_update = 1;
            $AQSDUpdate->app_update_date = date('Y-m-d');
            $AQSDUpdate->save();
            $InternalChannel = InstagramDataBmi::where('username', $aqcsd->username)
            ->first();
            // dd($InternalChannel);
            if(empty($InternalChannel)) {
                $InternalChannel = new InstagramDataBmi();
                // $InternalChannel->uuid = (string) Str::uuid();
                // $InternalChannel->plateform = 'instagram';
                // $InternalChannel->image = 'https://stage.bookmyinfluencers.com/favicon-16x16.png';
                // $InternalChannel->blur_image = 'https://stage.bookmyinfluencers.com/favicon-16x16.png';
                // $InternalChannel->channel_id = $aqcsd->id;
                echo Carbon::now() . '----' . $aqcsd->username . '----Inserted.' . PHP_EOL;
            }
            // $InternalChannel->uuid = (string) Str::uuid();
            
            $InternalChannel->username = $aqcsd->username;
            $InternalChannel->name = $aqcsd->name;
            $InternalChannel->bio = $aqcsd->bio;
            $InternalChannel->category = $aqcsd->category;
            $InternalChannel->followers = $aqcsd->followers;
            $InternalChannel->following = $aqcsd->following;
            $InternalChannel->profile_post = $aqcsd->profile_post;
            $InternalChannel->following_profile = $aqcsd->following_profile;
            $InternalChannel->avg_likes = $aqcsd->avg_likes;
            $InternalChannel->engmnt_rate = $aqcsd->engmnt_rate;
            $InternalChannel->fair_price = $aqcsd->fair_price;
            $InternalChannel->inf_score = $aqcsd->inf_score;
            $InternalChannel->credit_cost = ($aqcsd->creditcost > 0) ? $aqcsd->creditcost : '10';
            $InternalChannel->website = $aqcsd->website;
            $InternalChannel->emails = $aqcsd->emails;
            $InternalChannel->phone = $aqcsd->phone;
            $InternalChannel->gender = $aqcsd->gender;
            $InternalChannel->language = $aqcsd->language;
            $InternalChannel->country = $aqcsd->country;
            $InternalChannel->currency = $aqcsd->currency;
            $InternalChannel->inf_price = $aqcsd->inf_price;
            $InternalChannel->pitching_reel_price = $aqcsd->pitching_reel_price;
            $InternalChannel->pitching_price = $aqcsd->pitching_price;
            $InternalChannel->pitching_post_price = $aqcsd->pitching_post_price;
            $InternalChannel->story_price = $aqcsd->story_price;
            $InternalChannel->pitching_story_price = $aqcsd->pitching_story_price;
            $InternalChannel->status = $aqcsd->status;
            $InternalChannel->conversationemail = $aqcsd->conversationemail;
            $InternalChannel->managedby = $aqcsd->managedby;
            //$InternalChannel->freepromotion = $aqcsd->freepromotion;
            $InternalChannel->added_by = $aqcsd->added_by;
            $InternalChannel->added_date = $aqcsd->added_date;
            $InternalChannel->confirmed_on = $aqcsd->confirmed_on;
            $InternalChannel->updated_date = $aqcsd->updated_date;
            $InternalChannel->keyword_status = $aqcsd->keyword_status;
            $InternalChannel->autotag_status = $aqcsd->autotag_status;
            $InternalChannel->tags = $aqcsd->tags;
            $InternalChannel->tag_category_status = $aqcsd->tag_category_status;
            $InternalChannel->tag_category = $aqcsd->tag_category;
            $InternalChannel->inf_promotions = $aqcsd->inf_promotions;
            $InternalChannel->image = 'https://stage.bookmyinfluencers.com/favicon-16x16.png';
            $InternalChannel->blur_image = 'https://stage.bookmyinfluencers.com/favicon-16x16.png';
            
            // $credit_cost = ($aqcsd->creditcost > 0) ? $aqcsd->creditcost : '10';
            // $InternalChannel->canonical_name = $aqcsd->username;
            // $InternalChannel->channel = $aqcsd->username;
            // $InternalChannel->channel_link =  'https://www.instagram.com/' . $aqcsd->username;
            // $InternalChannel->name = $aqcsd->name;
            // $InternalChannel->number = $aqcsd->phone;
            // $InternalChannel->email = $aqcsd->emails;
            // $channelLangData = self::getAutoLang($aqcsd->language, $aqcsd->language);
            // $InternalChannel->channel_lang = $channelLangData; 
            // $InternalChannel->channel_name = $aqcsd->name;
            // $InternalChannel->channel_email = $aqcsd->emails;
            // $InternalChannel->tags = $aqcsd->tags;
            // $InternalChannel->status = '1'; // $aqcsd->status;
            // $InternalChannel->yt_description = $aqcsd->bio;
            // // $InternalChannel->views = $aqcsd->views;
            // $InternalChannel->subscribers = $aqcsd->followers;
            // $InternalChannel->videos = $aqcsd->profile_post;
            // // $InternalChannel->views_sub_ratio = $aqcsd->views_sub_ratio;
            // $InternalChannel->engagementrate = $aqcsd->engmnt_rate;
            // // $InternalChannel->price_view_ratio = $aqcsd->price_view_ratio;
            // $InternalChannel->currency = $aqcsd->currency;
            // // $InternalChannel->cat_percentile_1 = $aqcsd->cat_percentile_1;
            // // $InternalChannel->cat_percentile_5 = $aqcsd->cat_percentile_5;
            // // $InternalChannel->cat_percentile_10 = $aqcsd->cat_percentile_10;
            // // $InternalChannel->image_path = $aqcsd->image_path;
            // $InternalChannel->credit_cost = $credit_cost;
            // $InternalChannel->exposure = $aqcsd->avg_likes;
            // $InternalChannel->language = $aqcsd->language;
            // $InternalChannel->tag_category = $aqcsd->tag_category;
            // $InternalChannel->website = $aqcsd->website;
            // $InternalChannel->insta_following = $aqcsd->following;
            // $InternalChannel->insta_gender = $aqcsd->gender;
            // $InternalChannel->country = $aqcsd->country;
            // // $InternalChannel->facebook = $fb;
            // // $InternalChannel->twitter = $tw;
            // // $InternalChannel->instagram = $inst;
            // $InternalChannel->insta_inf_price = $aqcsd->inf_price;
            // $InternalChannel->insta_pitching_price = $aqcsd->pitching_price;
            // $InternalChannel->insta_story_price = $aqcsd->story_price;
            // $InternalChannel->fair_price = $aqcsd->fair_price;
            // $InternalChannel->currentdate = date('Y-m-d');
            $InternalChannel->save();

            $ChannelAsso = ChannelAssociation::where('internal_channel_id', $InternalChannel->id)->where('plateform_type', 2)->first();
            if (empty($ChannelAsso)) {
                $ChannelAsso = new ChannelAssociation();
            }
            $influ_id ='2';
            $ChannelAsso->plateform_type = 2;
            $ChannelAsso->influ_id = $influ_id;
            $ChannelAsso->internal_channel_id = $InternalChannel->id;
            $ChannelAsso->is_verified = 1;
            $ChannelAsso->yt_key = md5(random_bytes(12));
            $ChannelAsso->type = 0;
            $ChannelAsso->public_profile_url = null;
            $ChannelAsso->promotion_price = $aqcsd->inf_price;
            $ChannelAsso->barter = $aqcsd->barter;
            $ChannelAsso->is_default = 0;
            $ChannelAsso->save();
            if(!empty($aqcsd->emails)){
                $checkUser = User::where('email', $aqcsd->emails)->first();
                // dd($checkUser);
                echo Carbon::now() . '----' . $aqcsd->emails . '--USER:EMAIL:ID----.' . PHP_EOL;
                if(empty($checkUser) && $checkUser == 'null'){
                    $addUser = new User;
                    $addUser->userType = 'influencer';
                    $addUser->fullname = $aqcsd->name;
                    $addUser->phone = $aqcsd->phone;
                    $addUser->email = $aqcsd->emails;
                    $addUser->password = Hash::make(\Str::random(10));
                    $addUser->ip_address = \Request::ip();
                    $addUser->is_google = 0;
                    $addUser->google_id = null;
                    $addUser->status = '3';
                    $addUser->email_verified_token = \Str::random(36);
                    $addUser->account_type = '1';
                    $addUser->save();
                    $addUser->assignRole('influencer');
                    if($addUser){
                        $influ_id = $addUser->id;
                    }else{
                        $influ_id = $checkUser->id;
                    }
                }
            }
            $ChannelAsso->influ_id = $influ_id;
            $ChannelAsso->save();
            $counter++;
            echo Carbon::now() . '----' . $ChannelAsso->id . '--CA:ID----.' . PHP_EOL;
            echo Carbon::now() . '----' . $InternalChannel->id . '--INTRNL:ID----Updated.' . PHP_EOL;
            echo Carbon::now() . '----' . $aqcsd->id . '---' . $aqcsd->username . '--POOL--REF.' . PHP_EOL;
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
            $storagePath = Storage::disk('s3')->put('insta/blur_img/' . $prImage, $resource, 'public');
            $blur_image = Storage::disk('s3')->url('insta/blur_img/' . $prImage);
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
