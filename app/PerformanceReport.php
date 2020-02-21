<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PerformanceReport extends Model
{
    //
    protected $fillable  = [   'acc_id', 'g_id', 'report_type', 'cpa', 'cost', 
        'impressions', 'click', 'conversoin', 'cpc', 'totalConversion' ];
}
