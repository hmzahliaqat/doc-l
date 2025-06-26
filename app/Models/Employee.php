<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'user_id',
    ];

    public function company()
    {
        return $this->belongsTo(User::class);
    }
    public function sharedDocuments()
    {
        return $this->hasMany(SharedDocument::class);
    }
}
