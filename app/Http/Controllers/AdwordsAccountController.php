<?php

namespace App\Http\Controllers;

use App\AdwordsAccount;
use Illuminate\Http\Request;
use App\User;
use App\AccountChangeHistory;
use Illuminate\Support\Facades\DB;
use Validator;

class AdwordsAccountController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }
    
    /**
     * get a all Adwords Account.
     *
     * @return void
     */
    public function getAccountInfo(Request $request){
        if(isset($request['id']) && $request->id) {
          $account = AdwordsAccount::find($request->id);
          $history = $account->history();
          $alerts = $account->alerts();

          $director = User::select('name')
                    ->where('users.id','=',$account->account_director);

          $holders = User::select('name')
                     ->where('users.id','=',$account->account_manager)
                     ->union($director)
                     ->get()->toArray();
          $account['history'] = $history;
          $account['alerts'] = $alerts;
          $account['director_name'] = $holders[1]['name'];
          $account['manager_name'] = $holders[0]['name'];
          if($account) {
            return response()->json(
                    getResponseObject(true, $account, 200, '')
                    , 200);
          } else {
            return response()->json(
                    getResponseObject(false, '', 404, 'No account found')
                    , 404);
          }
        } else {
            return response()->json(
                    getResponseObject(false, '', 400, 'No valid id found')
                    , 400);
        }
    }
    

    /**
     * get a all Adwords Account.
     *
     * @return void
     */
    public function getAdwordsAccount(Request $request)
    {
        $accounts = [];
        if(isset($request['id']) && $request->id) {
           $accounts = AdwordsAccount::find($request->id);             
        } else {
            $priority = "'urgent','high','moderate','normal','low'";
            $accountsQuery = AdwordsAccount::
                        select('adwords_accounts.*', 'directors.name as director_name', 'managers.name as manager_name')
                        ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                        ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                        ->where('acc_status','!=','requiredSetup');
            $curntUser = (isset($request['userid'])) ? User::find($request->userid) : auth()->user();
            switch ($curntUser->Roles()->pluck('id')->first()) {
                case 1:
                    $accounts = $accountsQuery->orderByRaw("FIELD(acc_priority, $priority)")->get();
                    break;
                case 2:
                    $id= [];
                    $ids = User::select('id')->where('parent_id', '=', $curntUser->id)->get()->toArray();
                    foreach ($ids as $key => $value) { $id[] = $value['id']; }
                    $accounts = $accountsQuery->whereIn('account_director', $id)
                            ->orderByRaw("FIELD(acc_priority, $priority)")
                            ->get();
                    break;
                case 3:
                    $accounts = $accountsQuery->where('account_director', '=', $curntUser->id)
                        ->orderByRaw("FIELD(acc_priority, $priority)")
                        ->get();
                    break;
                case 4:
                    $accounts = $accountsQuery->where('account_manager', '=', $curntUser->id)
                        ->orderByRaw("FIELD(acc_priority, $priority)")
                        ->get();
                    break;
                default:
                    $accounts = null;
                    break;
            }
        }

        if($accounts != null) {
            return response()->json(
                    getResponseObject(true, $accounts, 200, '')
                    , 200);
        } else {
            return response()->json(
                    getResponseObject(false, '', 400, 'No valid role found')
                    , 400);
        }
    }


    /**
     * Add a new  Adwords Account.
     *
     * @return void
     */
    public function addAdwordsAccount(Request $request)
    {
        $validatedData = Validator::make($request->all(), 
            [
                'acc_name' => 'required|string|max:255',
                'g_acc_id' => 'required|string|max:10|min:10|unique:adwords_accounts',
                'budget' => 'required',
                'cpa' => 'required',
                'conversion_rate' => 'required',
                'account_director' => 'required|exists:users,id',
                'account_manager' => 'required|exists:users,id',
                'cron_time' => 'in:6,12,24',
                'acc_priority' => 'in:low,normal,moderate,high,urgent',
            ],
        );

        if ($validatedData->fails()) {
            return response()->json(
                    getResponseObject(false, array(), 400, $validatedData->errors()->first())
                    , 400);
        } else {
            $user = User::find($request->account_manager);
            if($user->Roles()->pluck('id')->first() != '4' || $user->parent_id != $request->account_director) {
                return response()->json(
                    getResponseObject(false, '', 400, 'Account Manager and Account Director isn`t connected')
                    , 400);
            } else {
                // To do : implement google adwords account verification before inserting into database
                $account = AdwordsAccount::create([
                    'acc_name' => $request->acc_name,
                    'g_acc_id' => $request->g_acc_id,
                    'budget' => $request->budget,
                    'cpa' => $request->cpa,
                    'conversion_rate' => $request->conversion_rate,
                    'add_by' => auth()->user()->id,
                    'account_director' => $request->account_director,
                    'account_manager' => $request->account_manager,
                    'cron_time' => isset($request['cron_time']) ? $request->cron_time : '24',
                    'acc_priority' => isset($request['acc_priority']) ? $request->acc_priority : 'normal',
                ]);
                return response()->json(
                    getResponseObject(true, $account, 200, '')
                    , 200);
            }
        }
    }

    /**
     * Update an existing  Adwords Account.
     *
     * @return void
     */
    public function updateAdwordsAccount(Request $request)
    {
        $valdRules = [
                'id' => 'required',
                'g_acc_id' => 'required|string|max:10|min:10',
                'acc_name' => 'string|max:255',
                'cron_time' => 'in:6,12,24',
                'acc_priority' => 'in:low,normal,moderate,high,urgent',
            ];
        if(isset($request['account_director']) || isset($request['account_manager'])) {
            $valdRules['account_director'] = 'required|exists:users,id';
            $valdRules['account_manager']  = 'required|exists:users,id';
        }
        $validatedData = Validator::make($request->all(), $valdRules);

        if ($validatedData->fails()) {
            return response()->json(
                    getResponseObject(false, array(), 400, $validatedData->errors()->first())
                    , 400);
        } else {
            $changes = array();
            $g_acc = AdwordsAccount::find($request->id);
            if($g_acc  && $g_acc->g_acc_id == $request->g_acc_id) {
                
                /* Changes to account name*/ 

                if(isset($request['acc_name']) && $request->acc_name != $g_acc->acc_name) {
                    $changes[] = changeHistoryField('acc_name', 'Account Name', $g_acc->acc_name, $request->acc_name, 'Account Name changed from `'.$g_acc->acc_name.'` to `'.$request->acc_name.'`');
                    $g_acc->acc_name = $request->acc_name;
                }

                /* Changes to account budget*/

                if(isset($request['budget']) && $request->budget != $g_acc->budget) {
                    $changes[] = changeHistoryField('budget', 'Account Budget', $g_acc->budget, $request->budget, 'Account Budget changed from `'.$g_acc->budget.'` to `'.$request->budget.'`');
                    $g_acc->budget = $request->budget;
                }

                /* Changes to account conversion_rate*/

                if(isset($request['conversion_rate']) && $request->conversion_rate != $g_acc->conversion_rate) {
                    $changes[] = changeHistoryField('conversion_rate', 'Account CPA', $g_acc->conversion_rate, $request->conversion_rate, 'Account Conversion Rate changed from `'.$g_acc->conversion_rate.'` to `'.$request->conversion_rate.'`');
                    $g_acc->conversion_rate = $request->conversion_rate;
                }

                /* Changes to account CPA*/

                if(isset($request['cpa']) && $request->cpa != $g_acc->cpa) {
                    $changes[] = changeHistoryField('cpa', 'Account CPA', $g_acc->cpa, $request->cpa, 'Account CPA changed from `'.$g_acc->cpa.'` to `'.$request->cpa.'`');
                    $g_acc->cpa = $request->cpa;
                }

                /* Changes to account impressions*/

                if(isset($request['impressions']) && $request->impressions != $g_acc->impressions) {
                    $changes[] = changeHistoryField('impressions', 'Account Impressions', $g_acc->impressions, $request->impressions, 'Account Impressions changed from `'.$g_acc->impressions.'` to `'.$request->impressions.'`');
                    $g_acc->impressions = $request->impressions;
                }

                /* Changes to account click*/

                if(isset($request['click']) && $request->click != $g_acc->click) {
                    $changes[] = changeHistoryField('click', 'Account Clicks', $g_acc->click, $request->click, 'Account Clicks changed from `'.$g_acc->click.'` to `'.$request->click.'`');
                    $g_acc->click = $request->click;
                }

                /* Changes to account acc_status*/

                if(isset($request['acc_status']) && $request->acc_status != $g_acc->acc_status) {
                    $changes[] = changeHistoryField('acc_status', 'Account Status', $g_acc->acc_status, $request->acc_status, 'Account Status changed from `'.$g_acc->acc_status.'` to `'.$request->acc_status.'`');
                    $g_acc->acc_status = $request->acc_status;
                }

                /* Changes to account conversion*/

                if(isset($request['conversion']) && $request->conversion != $g_acc->conversion) {
                    $changes[] = changeHistoryField('conversion', 'Account Conversion', $g_acc->conversion, $request->conversion, 'Account Conversions changed from `'.$g_acc->conversion.'` to `'.$request->conversion.'`');
                    $g_acc->conversion = $request->conversion;
                }


                /* Changes to account cpc*/
                if(isset($request['cpc']) && $request->cpc != $g_acc->cpc) {
                    $changes[] = changeHistoryField('cpc', 'Account CPC', $g_acc->cpc, $request->cpc, 'Account CPC changed from `'.$g_acc->cpc.'` to `'.$request->cpc.'`');
                    $g_acc->cpc = $request->cpc;
                }

                /* Changes to account ctr*/
                if(isset($request['ctr']) && $request->ctr != $g_acc->ctr) {
                    $changes[] = changeHistoryField('ctr', 'Account CTR', $g_acc->cpc, $request->ctr, 'Account CTR changed from `'.$g_acc->ctr.'` to `'.$request->ctr.'`');
                    $g_acc->ctr = $request->ctr;
                }

                /* Changes to account totalConversion*/
                if(isset($request['totalConversion']) && $request->totalConversion != $g_acc->totalConversion) {
                    $changes[] = changeHistoryField('cpc', 'Account Total Conversion', $g_acc->totalConversion, $request->totalConversion, 'Account Total Conversion changed from `'.$g_acc->cpc.'` to `'.$request->totalConversion.'`');
                    $g_acc->totalConversion = $request->totalConversion;
                }



                /*  Changes to Cron time  */
                if(isset($request['cron_time']) && $request->cron_time != $g_acc->cron_time) {
                    $changes[] = changeHistoryField('cron_time', 'Cron Time', $g_acc->cron_time, $request->cron_time, 'Cron Time changed from `'.$g_acc->cron_time.'` to `'.$request->cron_time.'`');
                    $g_acc->cron_time = $request->cron_time;
                }

                /*  Changes to Priority  */ 

                if(isset($request['acc_priority']) && $request->acc_priority != $g_acc->acc_priority) {
                    $changes[] = 
                    changeHistoryField('acc_priority', 'Priority', $g_acc->acc_priority, $request->acc_priority, 'Priority changed from `'.$g_acc->acc_priority.'` to `'.$request->acc_priority.'`');
                    $g_acc->acc_priority = $request->acc_priority;
                }

                /* Changes to account director/manager */ 

                if(isset($request['account_director']) || isset($request['account_manager'])) {
                    $user = User::find($request->account_manager);
                    if($user->Roles()->pluck('id')->first() != '4' || $user->parent_id != $request->account_director) {
                    return response()->json(
                            getResponseObject(false, '', 400, 'Account Manager and Account Director isn`t connected'), 400);
                    } else {
                        if($request->account_director != $g_acc->account_director) {
                            $changes[] = changeHistoryField('account_director', 'Account Director', $g_acc->account_director, $request->account_director, 'Account Director is changed');
                            $g_acc->account_director = $request->account_director;
                        }
                        if($request->account_manager != $g_acc->account_manager) {
                            $changes[] = changeHistoryField('account_manager', 'Account Manager', $g_acc->account_manager, $request->account_manager, 'Account manager is changed');
                            $g_acc->account_manager = $request->account_manager;
                        }
                    }
                }
                $g_acc->save();
                if($changes && count($changes) > 0) {
                    $history = AccountChangeHistory::create([
                        'acc_id' => $g_acc->id, 
                        'add_by' => auth()->user()->id, 
                        'changes' => $changes, 
                    ]);
                }
                return response()->json(
                    getResponseObject(true, $g_acc, 200, '')
                    , 200);
            }else {
                return response()->json(
                    getResponseObject(false, array(), 400, 'Account not matched')
                    , 400);
            }
        }
    }

    public function getUnassignedAccounts() {
        $accounts = [];
        $accounts = AdwordsAccount::select('adwords_accounts.*', 'directors.name as director_name', 'managers.name as manager_name')
                    ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                    ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                    ->where('acc_status', '=', 'requiredSetup')
                    ->orderBy('cpa', 'DESC')->orderBy('cpc', 'DESC')->orderBy('cost', 'DESC')
                    ->orderBy('conversion', 'DESC')->orderBy('click', 'DESC')
                    ->orderBy('impressions', 'DESC')->orderBy('totalConversion', 'DESC')
                    ->get();

        if($accounts != null) {
            return response()->json(
                    getResponseObject(true, $accounts, 200, '')
                    , 200);
        } else {
            return response()->json(
                    getResponseObject(false, '', 200, 'All no unassinged accounts found.')
                    , 400);
        }
    }

    public function updateUnassignedAccounts(Request $request) {
        $valdRules = [
            'account_director' => 'required|exists:users,id',
            'account_manager' => 'required|exists:users,id',
            'account_ids' => 'required'
        ];

        $validatedData = Validator::make($request->all(), $valdRules);

        if ($validatedData->fails()) {
            return response()->json(
                    getResponseObject(false, array(), 400, $validatedData->errors()->first())
                    , 400);
        } else {

            /* Changes to account director/manager */ 
            $user = User::find($request->account_manager);
            if($user->Roles()->pluck('id')->first() != '4' || $user->parent_id != $request->account_director) {
                return response()->json(
                    getResponseObject(false, '', 400, 'Account Manager and Account Director isn`t connected'), 400);
            } else {
                $ids = explode(',',$request->account_ids);
                $account = AdwordsAccount::whereIn('id',$ids)
                            ->update([
                                    'account_director' => $request->account_director,
                                    'account_manager' => $request->account_manager,
                                    'acc_status' => 'active'
                            ]);
                $changes = [];
                foreach($ids as $acc_id) {
                    $ch = array();
                    $ch[] = changeHistoryField('account_director', 'Account Director', 0, $request->account_director, 'Account Director is changed');
                    $ch[] = changeHistoryField('account_manager', 'Account Manager', 0, $request->account_manager, 'Account manager is changed');
                    $ch[] = changeHistoryField('acc_status', 'Account Status', 'requiredSetup', 'active', 'Account Status changed from `requiredSetup` to `active`');
                    $changes[] = array( 
                                    'acc_id' => $acc_id, 
                                    'add_by' => auth()->user()->id, 
                                    'changes'=> $ch,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                );
                    AccountChangeHistory::create(array( 
                        'acc_id' => $acc_id, 
                        'add_by' => auth()->user()->id, 
                        'changes'=> $ch
                    ));
                }
                return response()->json(
                    getResponseObject(true, 'Account assigned successfully', 200, '')
                    , 200);
            }
        }
    }
}
