<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'user_id',
        'otp_enabled',
    ];

    /**
     * Get the user that owns the setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
