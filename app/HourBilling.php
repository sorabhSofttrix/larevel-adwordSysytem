<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HourBilling extends Model
{
    //
    protected $fillable = ['acc_id', 'project_id', 'hours', 'stage', 'add_by'];
}
