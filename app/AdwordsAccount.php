<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdwordsAccount extends Model
{
    //
    protected $fillable = [
      'g_acc_id','acc_name','budget','cpa','conversion_rate','cron_time','priority','account_director',
      'account_manager', 'add_by'
    ];
}
