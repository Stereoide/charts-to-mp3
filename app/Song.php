<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = ['artist', 'name', 'youtube_id', 'converted', ];
}
