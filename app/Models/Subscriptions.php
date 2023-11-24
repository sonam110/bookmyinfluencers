<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SubscriptionPlan;

class Subscriptions extends Model {

    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'user_id',
        'amount',
        'tax',
        'tax_amount',
        'total',
        'subscribed_at',
        'expire_at',
        'status',
    ];

    public function plan() {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id', 'id');
    }

}
