<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountIssue extends Model
{
    // 
    protected $fillable =  ['acc_id', 'error', 'status', 'comments', 'resolved_by', 'resolved_at'];
}
