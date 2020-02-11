<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountChangeHistory extends Model
{
    //
	protected $fillable = ['acc_id', 'add_by', 'changes'];

    protected $casts = [
     'changes' => 'array'
	];
}
