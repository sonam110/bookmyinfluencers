<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfluencerChannel extends Model {

    use HasFactory;

    protected $table = 'influencerchannels';
    protected $connection = 'mysql3';

}
