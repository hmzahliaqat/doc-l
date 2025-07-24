<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Partial extends Model
{

    protected $fillable = [
        'user_id',
        'employee_id',
        'document_id',
        'file_path',
        'file_type'
    ];

    public function getFilePathAttribute($value)
    {
        if ($value && $this->file_type !== 'literal') {
            $filename = basename($value);
            return url("/storage/partials/{$filename}");
        }

        return  $value;
    }


}

