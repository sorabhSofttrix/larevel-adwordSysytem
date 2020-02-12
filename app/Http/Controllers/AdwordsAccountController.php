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
          $account['history'] = $history;
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
            switch (auth()->user()->Roles()->pluck('id')->first()) {
                case 1:
                    $accounts = AdwordsAccount::
                        select('adwords_accounts.*', 'directors.name as director_name', 'managers.name as manager_name')
                        ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                        ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                        ->get();
                    break;
                case 2:
                    $id= [];
                    $ids = User::select('id')->where('parent_id', '=', auth()->user()->id)->get()->toArray();
                    foreach ($ids as $key => $value) { $id[] = $value['id']; }
                    $accounts = AdwordsAccount::
                            select('adwords_accounts.*', 'directors.name as director_name', 'managers.name as manager_name')
                            ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                            ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                            ->whereIn('account_director', $id)
                            ->get();
                    break;
                case 3:
                    $accounts = AdwordsAccount::
                        select('adwords_accounts.*', 'directors.name as director_name', 'managers.name as manager_name')
                        ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                        ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                        ->where('account_director', '=', auth()->user()->id)
                        ->get();
                    break;
                case 4:
                    $accounts = AdwordsAccount::
                        select('adwords_accounts.*', 'directors.name as director_name', 'managers.name as manager_name')
                        ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                        ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                        ->where('account_manager', '=', auth()->user()->id)
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

                /* Changes to account CPA*/

                if(isset($request['cpa']) && $request->cpa != $g_acc->cpa) {
                    $changes[] = changeHistoryField('cpa', 'Account CPA', $g_acc->cpa, $request->cpa, 'Account CPA changed from `'.$g_acc->cpa.'` to `'.$request->cpa.'`');
                    $g_acc->cpa = $request->cpa;
                }

                /* Changes to account conversion_rate*/

                if(isset($request['conversion_rate']) && $request->conversion_rate != $g_acc->conversion_rate) {
                    $changes[] = changeHistoryField('conversion_rate', 'Account CPA', $g_acc->conversion_rate, $request->conversion_rate, 'Account Conversion Rate changed from `'.$g_acc->conversion_rate.'` to `'.$request->conversion_rate.'`');
                    $g_acc->conversion_rate = $request->conversion_rate;
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
}
