<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelUpdate extends Model {

    use HasFactory;

    protected $table = 'channel_social';
    protected $connection = 'mysql2';

}
