<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model {

    use HasFactory;

    protected $fillable = [
        'fullname',
        'email',
        'who_are_you',
        'skype_whats_app',
        'message',
        'ip_address',
    ];

}
