<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionTopup extends Model {

    use HasFactory;

    protected $fillable = [
        'subscription_plan_current_id',
        'user_id',
        'amount',
        'additional_credits',
        'pg_order_id',
        'total',
        'tax_amount',
        'tax',
        'status',
    ];

}
