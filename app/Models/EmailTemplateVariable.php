<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_template_id',
        'variable_name',
        'display_name',
        'default_value',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
