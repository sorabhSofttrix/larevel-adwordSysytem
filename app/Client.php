<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    //
    protected $fillable  =  [ 'client_name', 'email', 'skype', 'add_by', 'is_active', 'phone'];
}
