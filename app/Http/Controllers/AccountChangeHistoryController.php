<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AccountChangeHistory;
use Illuminate\Support\Facades\DB;
use App\User;

class AccountChangeHistoryController extends Controller
{
    /**
     * Create a new  instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }

    public function getCounts($type = 'active', $currentYear, $additional_conditions = []) {
        $query = AccountChangeHistory::select(DB::raw('substr(created_at,6,2) as month,COUNT(DISTINCT(acc_id)) as count'))
                    ->whereRaw('YEAR(created_at) = '.$currentYear)
                    ->whereRaw("JSON_CONTAINS(changes, json_object('field','acc_status'))")
                    ->WhereRaw("JSON_CONTAINS(changes, json_object('new_value','".$type."'))")
                    ->orderBy('month')
                    ->groupBy('month');
        foreach($additional_conditions as $key => $value) {
            $query->whereRaw($value);
        }
        return $query->get();
    }

    public function getAccountMonthlyStatusSummary(Request $request) {
        $currentYear = date("Y");
        $GLOBALS['summaray_data'] = array( 
                        '01'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '02'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '03'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '04'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '05'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '06'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '07'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '08'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '09'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '10'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '11'=> array( 'active' => null,'paused' => null,'closed' => null), 
                        '12'=> array( 'active' => null,'paused' => null,'closed' => null)
        );
        $additional_conditions = [];
        $curntUser = (isset($request['userid'])) ? User::find($request->userid) : auth()->user();
        switch ($curntUser->Roles()->pluck('id')->first()) {
            case 2:
                $ids = [];
                $ids = User::select('id')->where('parent_id', '=', $curntUser->id)->get()->toArray();
                $conditons = '';
                foreach($ids as $key => $value) {
                    $conditons .= ($conditons == '') 
                                    ? "( json_contains(changes, json_object('new_value','".$value['id']."'))"
                                    : "OR json_contains(changes, json_object('new_value','".$value['id']."'))";
                    $conditons .= ($key + 1 == count($ids)) ? ')' : '';
                }
                $conditons = ($conditons) ? 'and '.$conditons : '';
                $additional_conditions[] = "acc_id in (SELECT acc_id from account_change_histories where substr( json_extract(changes, '$[*].field'), POSITION('account_director' IN json_extract(changes, '$[*].field')), 16) <> '' ".$conditons." )";
                break;
            case 3:
                $additional_conditions[] = "acc_id in (
                        select acc_id from account_change_histories 
                        where JSON_CONTAINS(changes, json_object('field','account_director')) 
                                and JSON_CONTAINS(changes, json_object('new_value', '".$curntUser->id."'))
                )";
                break;
            case 4:
                $additional_conditions[] = "acc_id in (
                    select acc_id from account_change_histories 
                    where JSON_CONTAINS(changes, json_object('field','account_manager')) 
                          and JSON_CONTAINS(changes, json_object('new_value', '".$curntUser->id."'))
                )";
                break;
        }
        $paused_accounts = $this->getCounts('paused', $currentYear, $additional_conditions);
        $closed_accounts = $this->getCounts('closed', $currentYear, $additional_conditions);
        $active_accounts = $this->getCounts('active', $currentYear, $additional_conditions);

        foreach( $GLOBALS['summaray_data'] as $key => $value) {
            // for active
            array_filter($active_accounts->toArray(), function ($var) use ($key) {
                if($var['month'] == $key) {
                    $GLOBALS['summaray_data'][$key]['active'] = $var['count'];
                }
            });

            // for paused
            array_filter($paused_accounts->toArray(), function ($var) use ($key) {
                if($var['month'] == $key) {
                    $GLOBALS['summaray_data'][$key]['paused'] = $var['count'];
                }
            });

            // for closed
            array_filter($closed_accounts->toArray(), function ($var) use ($key) {
                if($var['month'] == $key) {
                    $GLOBALS['summaray_data'][$key]['closed'] = $var['count'];
                }
            });
        }
        $data = $GLOBALS['summaray_data'];
        unset($GLOBALS['summaray_data']);
        return response()->json(
            getResponseObject(true, $data, 200, '')
            , 200);
    }

    public function getAccountsInDateRange(Request $request) {
        $mainQuery = AccountChangeHistory::select(DB::raw('account_change_histories.*, adwords_accounts.g_acc_id, acc_status, account_director, account_manager'))
                    ->leftJoin('adwords_accounts','acc_id','adwords_accounts.id')
                    ->whereRaw("account_change_histories.created_at BETWEEN '".$request->from."' AND '".$request->to."'")
                    ->whereRaw("JSON_CONTAINS(changes, json_object('field','acc_status'))")
                    ->WhereRaw("JSON_CONTAINS(changes, json_object('new_value','".$request->status."'))")
                    ->orderBy('created_at','desc');
        $additional_conditions = [];
        $curntUser = (isset($request['userid'])) ? User::find($request->userid) : auth()->user();
        switch ($curntUser->Roles()->pluck('id')->first()) {
            case 2:
                $ids = [];
                $ids = User::select('id')->where('parent_id', '=', $curntUser->id)->get()->toArray();
                $conditons = '';
                foreach($ids as $key => $value) {
                    $conditons .= ($conditons == '') 
                                    ? "( json_contains(changes, json_object('new_value','".$value['id']."'))"
                                    : "OR json_contains(changes, json_object('new_value','".$value['id']."'))";
                    $conditons .= ($key + 1 == count($ids)) ? ')' : '';
                }
                $conditons = ($conditons) ? 'and '.$conditons : '';
                // $additional_conditions[] = "acc_id in ( SELECT acc_id from account_change_histories where JSON_CONTAINS(changes, json_object('field','account_director')) ".$conditons;
                
                $additional_conditions[] = "acc_id in (
                    select acc_id from account_change_histories 
                    where JSON_CONTAINS(changes, json_object('field','account_director')) 
                    ".$conditons." )";
                break;
            case 3:
                $additional_conditions[] = "acc_id in (
                        select acc_id from account_change_histories 
                        where JSON_CONTAINS(changes, json_object('field','account_director')) 
                                and JSON_CONTAINS(changes, json_object('new_value', '".$curntUser->id."'))
                )";
                break;
            case 4:
                $additional_conditions[] = "acc_id in (
                    select acc_id from account_change_histories 
                    where JSON_CONTAINS(changes, json_object('field','account_manager')) 
                          and JSON_CONTAINS(changes, json_object('new_value', '".$curntUser->id."'))
                )";
                break;
        }
        foreach($additional_conditions as $key => $value) {
            $mainQuery->whereRaw($value);
        }
        $data = $mainQuery->get()->toArray();
        $fetchedIds = [];
        foreach($data as $key => $value) {
            if(!in_array( $value['g_acc_id'], $fetchedIds)) {
                $fetchedIds[]= $value['g_acc_id'];
            }
        }
        
        return response()->json(
            getResponseObject(true, $fetchedIds, 200, '')
            , 200);
    }
}
