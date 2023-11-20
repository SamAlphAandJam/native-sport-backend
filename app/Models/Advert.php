<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advert extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'page',
        'media_url',
        'duration',
        'width',
        'height',
        'country',
        'platform',
        'section',
        'redirect_url',
        'mediaType',
        'name'
    ];
}
