<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminSetting extends Model
{
    protected $fillable = [
        'app_name',
        'app_logo',
        'video_url',
        'stripe_app_key',
        'stripe_secret_key',
    ];
}
