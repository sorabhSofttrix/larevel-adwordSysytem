<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\Models\Media;

class AccountSync extends Model implements HasMedia
{
	use HasMediaTrait;
    protected $fillable = ['add_by','status'];
    
}
