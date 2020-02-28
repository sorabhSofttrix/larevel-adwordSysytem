<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountStatusReason extends Model
{
    //
    protected $fillable = ['title', 'is_active', 'rank', 'sortOrder','add_by'];
}
