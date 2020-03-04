<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AllComment extends Model
{
    //
    protected $fillable = ['entity_type', 'entity_id', 'comment', 'add_by', 'is_deleted', 'parent_id'];
}
