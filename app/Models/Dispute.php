<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderProcess;
use App\Models\Order;
class Dispute extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'reason',
        'reason1',
        'comment',
        'amount',
        'status',
    ];

    public function userInfo() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function order() {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
    public function orderProcess() {
        return $this->belongsTo(OrderProcess::class, 'order_id', 'order_id');
    }

   

}
