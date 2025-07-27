<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SharedDocument extends Model
{
    protected $fillable = [
        'shared_document_name',
        'document_id',
        'user_id',
        'employee_id',
        'access_hash',
        'status',
        'signed_at',
        'valid_for',
        'file_path',
        'pdf_path',
        'pages',
        'canvas',
        'is_signable',
        'can_add_picture',
        'can_add_text',
        'view_pages',

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship with Document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }


    /**
     * Get the share URL
     */
    public function getShareUrlAttribute(): string
    {
        return route('documents.shared', ['hash' => $this->access_hash]);
    }

    public function getSignedAtAttribute($value){
        return $value ? Carbon::parse($value)->diffForHumans() : 'N/A';
    }

    public function getStatusAttribute($value){
        return $value == 1 ? 'Signed' : 'Pending';
    }



    public function isExpired()
    {
        return Carbon::parse($this->created_at)
            ->addMinutes((int) $this->valid_for)
            ->isPast();
    }

    /**
     * Get all signed documents (where status = 1)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSigned()
    {
        return self::where(['status' => 1, 'user_id' => Auth::id()])->get();
    }
}
