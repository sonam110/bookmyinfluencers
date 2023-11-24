<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Service extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_affiliate_program',
        'is_managment_service',
    ];

    public function userInfo() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
