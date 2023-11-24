<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\User;
use App\Models\Channel;

class Campaign extends Model {

    use HasFactory;

    protected $fillable = [
        'uuid',
        'brand_id',
        'plateform',
        'brand_name',
        'brand_url',
        'brand_logo',
        'camp_title',
        'camp_desc',
        'duration',
        'promotion_start',
        'promot_product',
        'script_approval',
        'reference_videos',
        'tag_category',
        'subscriber',
        'average_view',
        'currency',
        'budget',
        'deal_type',
        'compensation',
        'compensation_desc',
        'country',
        'views_commitment',
        'engagement_rate',
        'lang',
        'status',
        'reason',
        'invite_only',
        'visibility',
        'created_on',
        'expired_on',
        'plateform_type',
    ];

    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function getBudgetAttribute() {
        $user_currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
        if($this->attributes['currency'] != $user_currency){
            return round($this->attributes['budget'] * auth()->user()->ops_currency_rate,2);
            
        } else {
            return $this->attributes['budget'];
        }

       
    }

    public function brandInfo() {
        return $this->belongsTo(User::class, 'brand_id', 'id');
    }

    public function infInfo() {
        return $this->belongsTo(User::class, 'influ_id', 'id');
    }

    public function channelInfo() {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

    public function categoryInfo() {
        return Category::whereIn('id', $this->category);
    }

    protected $appends = ['category_info_list'];

    public function getCategoryInfoListAttribute() {
        return $this->categoryInfo()->pluck('name')->join(',');
    }

   
    public function getCategoriesAttribute() {


        if (!$this->relationLoaded('categories')) {


            $categories = Category::whereIn('id', $this->category)->get();

            $this->setRelation('categories', $categories);
        }

        return $this->getRelation('categories');
    }

    
    public function categories() {

        return Category::whereIn('id', $this->category);
    }

  
    public function getCategoryAttribute($commaSeparatedIds) {
        return ($commaSeparatedIds) ? explode(',', $commaSeparatedIds) : [];
    }

   
    public function setCategoryAttribute($ids) {

        $this->attributes['category'] = (!empty($ids) && is_array($ids)) ? implode(',', $ids)  : $ids ;
    }

}
