<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelViews extends Model {

    use HasFactory;

    protected $table = 'channel_views';
    protected $connection = 'mysql2';

}
