<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable =  [ 'acc_id', 'g_id', 'report_type', 'alerts', 'comments', 'status', 'resolved_by', 'resolved_at'];
    protected $casts = [
        'alerts' => 'array'
       ];
}
