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
        $history = $this->hasMany('App\AccountChangeHistory', 'acc_id')->get();
        $allUserId = [];
        foreach ($history as $key => $value) { $allUserId[] = $value['add_by']; }
        $allUsers = User::select('id','name')->whereIn('id', $allUserId)->get();
        foreach ($history as $key => $value) {
        	$searchedValue = $value['add_by'];
        	$users = array_reduce($allUsers->toArray(), function ($result, $item) use ($searchedValue) {
        		return $item['id'] == $searchedValue ? $item : $result;});
        	$history[$key]['user'] = $users;
        }
        return $history;
    }

    public function alerts() {
      $alerts = $this->hasMany('App\Alert', 'acc_id')
                ->leftJoin('users','alerts.id', '=', 'users.id')
                ->select('users.name as resolver_name','alerts.*')
                ->get();
      return $alerts;
  }
}
