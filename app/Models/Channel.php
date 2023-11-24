<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\ChannelSearchData;
use App\Models\User;
use App\Models\Revealed;

class Channel extends Model {

    use HasFactory;

    protected $casts = [
        'is_default' => 'boolean',
    ];
    protected $fillable = [
        'uuid',
        'id',
        'channel_id',
        'canonical_name',
        'channel',
        'plateform',
        'channel_link',
        'channel_lang',
        'channel_name',
        'public_profile_url',
        'tag_category',
        'tags',
        'image',
        'blur_image',
        'yt_description',
        'views',
        'subscribers',
        'videos',
        'views_sub_ratio',
        'engagementrate',
        'price_view_ratio',
        'currency',
        'cat_percentile_1',
        'cat_percentile_5',
        'cat_percentile_10',
        'image_path',
        'credit_cost',
        'exposure',
        'language',
        'tag_category'
    ];

    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

//    public function channelInfo() {
//        return $this->belongsTo(ChannelSearchData::class, 'channel_id', 'id');
//    }
//    public function channelEng() {
//        return $this->belongsTo(ChannelEngagement::class, 'channel_id', 'channel_id');
//    }

    public function revealedChannel() {
        return $this->belongsTo(Revealed::class, 'channel_id', 'channel_id');
    }
    protected $appends = ['channel_tags'];
    /*public function getChannelLangAttribute() {
        $lang = $this->attributes['channel_lang'];
        if($this->attributes['channel_lang'] !=''){
            $json = $this->attributes['channel_lang'];
            $jsondecode = json_decode($json , true);
            if(is_array($jsondecode)) {
                $keys = array_keys($jsondecode);
                $lang = (@$keys[0] !='Undefined') ? @$keys[0] :NULL;
            }
           
        }
        return $lang;
       
    }*/

    public function getChannelTagsAttribute() {
        $tags = NULL;
        if(!empty($this->attributes['tags'] )){
            $json = $this->attributes['tags'];
            $jsondecode = json_decode($json , true);
            if(!empty($jsondecode) && is_array($jsondecode)) {
                $keys = array_keys($jsondecode);
                $tags = implode(',',$keys);
            }
           
        }
        
        return $tags;
       
    }

}
