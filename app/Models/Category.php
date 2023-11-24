<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model {

    use HasFactory;

    protected $fillable = [
        'name',
        'is_parent',
        'status',
    ];

    public function getCampaign() {
        return $this->hasMany('App\Models\Campaign', 'id', 'brand_id');
    }

}
