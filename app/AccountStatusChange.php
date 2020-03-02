<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountStatusChange extends Model
{
    // 
    protected $fillable =  ['new_value' , 'old_value' , 'comment' , 'up_comments' , 'rating' , 'add_by' , 'reason_id' , 'reason_text' , 'acc_id', 'history_id'];
}
