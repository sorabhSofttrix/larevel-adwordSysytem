<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class AdwordsAccount extends Model
{
    //
    protected $fillable = [
      'g_acc_id','acc_name','budget','conversion_rate','cron_time','priority','account_director',
      'account_manager', 'add_by', 'ctr', 'cpa','cost', 'impressions', 'click', 'conversion', 
      'cpc', 'totalConversion', 'have_issue'
    ];

    /**
    * Link History with adword account
    *
    *
    */
    public function history() {
        $history = $this->hasMany('App\AccountChangeHistory', 'acc_id')
                    ->select('account_change_histories.*', 
                             'ascs.reason_id','ascs.comment','ascs.up_comments','ascs.rating','ascs.id as ascs_id',
                             'asrs.title as reason_text')
                    ->leftJoin('account_status_changes as ascs','account_change_histories.id','ascs.history_id')
                    ->leftJoin('account_status_reasons as asrs','ascs.reason_id','asrs.id')
                    ->get();
        $allUserId = [];
        foreach ($history as $key => $value) {
          $allUserId[] = $value['add_by'];
          $changes = $value['changes'];
          $fieldArray = array('account_director','account_manager');
          foreach($changes as $change) {
            if(in_array($change['field'], $fieldArray)) {
              $allUserId[] = $change['new_value'];
              $allUserId[] = $change['old_value'];
            }
          }
        }
        $allUsers = User::select('id','name')->whereIn('id', $allUserId)->get();
        $finalHistory['data'] = $history;
        $finalHistory['users'] = $allUsers->toArray(); 
        return $finalHistory;
    }

    public function alerts() {
      $alerts = $this->hasMany('App\Alert', 'acc_id')
                ->leftJoin('users','alerts.id', '=', 'users.id')
                ->select('users.name as resolver_name','alerts.*')
                ->get();
      return $alerts;
  }
}
