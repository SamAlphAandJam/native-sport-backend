<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralNews extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'uri',
        'dates',
        'byline',
        'headline',
        'description_text',
        'description_html',
        'body_text',
        'body_html',
        'subject',
        'language',
        'picture',
        'ranking'
    ];

    protected $casts = [
        'subject' => 'array', 'dates' => 'array',
    ];
}
