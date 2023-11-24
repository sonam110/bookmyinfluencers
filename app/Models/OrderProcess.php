<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProcess extends Model {

    use HasFactory;

    protected $fillable = [
        'order_id',
        'orderRandId',
        'application_id',
        'influ_id',
        'brand_id',
        'camp_id',
        'channel_id',
        'job_description',
        'template_script',
        'camp_script_approval_date',
        'comment',
        'video_script',
        'video_script_approval_date',
        'video_script_desc',
        'video_preview',
        'video_prev_approval_date',
        'live_video',
        'live_video_approval_date',
        'promo_text_link',
        'is_text_link_provided',
        'stage',
        'status',
    ];

    public function appInfo() {
        return $this->belongsTo(AppliedCampaign::class, 'application_id', 'id');
    }

    public function infInfo() {
        return $this->belongsTo(User::class, 'influ_id', 'id');
    }

    public function campInfo() {
        return $this->belongsTo(Campaign::class, 'camp_id', 'id');
    }

    public function chanelInfo() {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

}
