<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\Models\Media;
use App\AllComment;

class Project extends Model implements HasMedia
{
    use HasMediaTrait;
    //
    protected $fillable  = [ 'project_name', 'contract_start_date', 'hourly_rate',
             'weekly_limit', 'questionnaire', 'sales_person', 'profile', 'client', 'add_by'];
    
    public function comments() {
        return AllComment::select('all_comments.id','all_comments.comment','all_comments.created_at',
                                  'all_comments.add_by','users.name as added_by_name')
                    ->leftJoin('users','all_comments.add_by','users.id')
                    ->where('is_deleted',false)
                    ->where('entity_type','project')
                    ->where('entity_id',$this->id)
                    ->get();
    }
}
