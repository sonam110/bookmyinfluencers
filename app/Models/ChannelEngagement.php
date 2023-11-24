<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelEngagement extends Model {

    use HasFactory;

    public $timestamps = false;
    protected $table = 'channel_engagement';
    protected $connection = 'mysql2';

}
