<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramData extends Model
{
    use HasFactory;
    protected $table = 'instagram_data';
   // protected $connection = 'mysql2';
    public $timestamps = false;
}
