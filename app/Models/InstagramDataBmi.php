<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramDataBmi extends Model
{
    use HasFactory;
    protected $table = 'instagram_data';
    public $timestamps = false;
}