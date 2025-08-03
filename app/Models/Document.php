<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'pdf_id',
        'name',
        'file_path',
        'pages',
        'canvas',
        'user_id',
        'is_trashed',
        'is_archived',
        'trash_date',
        'update_date'
    ];

    protected $casts = [
        'canvas' => 'array',
    ];

    public function getUpdateDateAttribute($value)
    {
        return (int) $value;
    }



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sharedDocuments()
    {
        return $this->hasMany(SharedDocument::class);
    }


    public function getFilePathAttribute($attribute): string
    {
        return asset('storage/' . $attribute);
    }

    public function getPdfUrlAttribute(): string
    {
        if (strpos($this->pdf_path, 'documents') === 0) {
            return asset('storage/' . $this->pdf_path);
        }

        return asset('storage/' . $this->file_path);
    }



    public function isAuthorized($user_id)
    {
        return $user_id == Auth::id();
    }


}
