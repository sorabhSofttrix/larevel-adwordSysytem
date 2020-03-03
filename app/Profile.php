<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    // 
    protected $fillable  =  [ 'profile_name', 'profile_id', 'username', 'email', 'password', 'add_by', 'is_active'];

    protected $hidden = [ 'password' ];
}
