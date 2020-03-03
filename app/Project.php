<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\Models\Media;

class Project extends Model implements HasMedia
{
    use HasMediaTrait;
    //
    protected $fillable  = [ 'project_name', 'contract_start_date', 'hourly_rate',
             'weekly_limit', 'questionnaire', 'sales_person', 'profile', 'client', 'add_by'];
}
