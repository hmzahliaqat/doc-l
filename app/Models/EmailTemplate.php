<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'is_active',
        'template_type',
        'blade_content',
    ];

    public function variables()
    {
        return $this->hasMany(EmailTemplateVariable::class);
    }
}
