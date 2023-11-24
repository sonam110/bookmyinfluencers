<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Channel;
use App\Models\Category;
use App\Models\Subscriptions;
use App\Models\RelationshipManager;
use Illuminate\Support\Str;

class User extends Authenticatable {

    use HasApiTokens,
        HasFactory,
        Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'userType',
        'fullname',
        'email',
        'password',
        'google_id',
        'phone',
        'profile_photo',
        'cover_photo',
        'language',
        'address',
        'company_name',
        'company_address',
        'skype',
        'whats_app',
        'country_id',
        'currency',
        'pan_no',
        'gst',
        'credit_reminder',
        'notification',
        'default_bank',
        'wallet_balance',
        'reserved_balance',
        'remaining_balance',
        'credit_balance',
        'customer_id',
        'category_preferences',
        'token',
        'is_google',
        'ip_address',
        'account_type',
        'is_login_first_time',
        'last_topup_date',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    protected $appends = ['channel', 'category_info_list','auto_fetch_channel'];

    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

   
    public function RelationshipManager() {
        return $this->belongsTo(RelationshipManager::class, 'manager_id', 'id');
    }

    public function channel() {
        return Channel::select('channels.id','channels.channel_name','channel_association.internal_channel_id','channel_association.is_default','channel_association.is_verified','channel_association.influ_id','channel_association.promotion_price')->join('channel_association', 'channels.id', '=', 'channel_association.internal_channel_id')
                        ->where('channel_association.influ_id', $this->id)->where('channel_association.is_default', '1');
    }
    public function getAutoFetchChannelAttribute() {
        $channel = Channel::select('id','channel_email','channel_name','channel_lang')->where('channel_email', $this->email)->first();
        return $channel;
    }

    public function allChannels() {
        return $this->hasMany(Channel::class, 'channel_email', 'email');
       
    }
    

    public function getChannelAttribute() {
        return $this->channel()->first();
    }

    public function categoryInfo() {
        return explode(',', $this->category_preferences);
    }

    public function getCategoryInfoListAttribute() {
        return Category::whereIn('id', $this->categoryInfo())->pluck('name')->join(',');
    }

}
