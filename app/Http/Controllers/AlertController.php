<?php

namespace App\Http\Controllers;

use App\Alert;
use App\AdwordsAccount;
use App\User;
use Illuminate\Http\Request;
use Validator;

class AlertController extends Controller
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
     * get a all dashboard alerts Account.
     *
     * @return void
     */
    public function getAllAlertsForDashboard(Request $request)
    {
        $alerts = [];
        $accounts = [];
        switch (auth()->user()->Roles()->pluck('id')->first()) {
            case 1:
                $accounts = AdwordsAccount::select('id')->get();
                break;
            case 2:
                $id= [];
                $ids = User::select('id')->where('parent_id', '=', auth()->user()->id)->get()->toArray();
                foreach ($ids as $key => $value) { $id[] = $value['id']; }
                $accounts = AdwordsAccount::select('id')->whereIn('account_director', $id)->get();
                break;
            case 3:
                $accounts = AdwordsAccount::select('id')->where('account_director', '=', auth()->user()->id)->get();
                break;
            case 4:
                $accounts = AdwordsAccount::select('id')->where('account_manager', '=', auth()->user()->id)->get();
                break;
            default:
                $accounts = null;
                break;
        }

        if($accounts) {
            $accountsIds = $accounts->toArray();
            $alerts = Alert::
                    select('alerts.*', 'adwords_accounts.acc_name','adwords_accounts.account_director', 'adwords_accounts.account_manager','directors.name as director_name', 'managers.name as manager_name')
                    ->leftJoin('adwords_accounts', 'alerts.acc_id', '=', 'adwords_accounts.id')
                    ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                    ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                    ->whereIn('alerts.acc_id', $accountsIds)
                    ->where('alerts.status','=', 'open')
                    ->get();
            if(count($alerts)) {
                return response()->json(
                        getResponseObject(true, $alerts, 200, '')
                        , 200);
            } else {
                return response()->json(
                        getResponseObject(false, array(), 200, 'No Alerts Found')
                        , 200);
            }
        } else {
            return response()->json(
                getResponseObject(false, '', 400, 'No Account Found.')
                , 400);
        }

    }

    public function updateAlert(Request $request){
        $validatedData = Validator::make($request->all(), 
            [
                'id' => 'required|exists:alerts,id',
                'comments' => 'required',
            ],
        );

        if ($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $alert = Alert::find($request->id);
            if($alert) {
                $alert->comments = $request->comments;
                $alert->resolved_by = auth()->user()->id;
                $alert->resolved_at = date('Y-m-d H:i:s');
                $alert->status = 'resolved';
                $alert->save();
                return response()->json(
                    getResponseObject(true, 'Issue Resolved', 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, array(), 400, 'Alert not found')
                    , 400);
            }
        }
    }


    /**
     * get a all dashboard alerts count .
     *
     * @return void
     */
    public function getAlertsCountForDashboard(Request $request)
    {
        $alerts = [];
        $accounts = [];
        switch (auth()->user()->Roles()->pluck('id')->first()) {
            case 1:
                $accounts = AdwordsAccount::select('id')->get();
                break;
            case 2:
                $id= [];
                $ids = User::select('id')->where('parent_id', '=', auth()->user()->id)->get()->toArray();
                foreach ($ids as $key => $value) { $id[] = $value['id']; }
                $accounts = AdwordsAccount::select('id')->whereIn('account_director', $id)->get();
                break;
            case 3:
                $accounts = AdwordsAccount::select('id')->where('account_director', '=', auth()->user()->id)->get();
                break;
            case 4:
                $accounts = AdwordsAccount::select('id')->where('account_manager', '=', auth()->user()->id)->get();
                break;
            default:
                $accounts = null;
                break;
        }

        if($accounts) {
            $accountsIds = $accounts->toArray();
            $alerts = Alert::select('id')
                    ->whereIn('acc_id', $accountsIds)
                    ->where('status','=', 'open')
                    ->count();
            if($alerts != null) {
                return response()->json(
                        getResponseObject(true, $alerts, 200, '')
                        , 200);
            } else {
                return response()->json(
                        getResponseObject(false, array(), 200, 'No Alerts Found')
                        , 200);
            }
        } else {
            return response()->json(
                getResponseObject(false, '', 400, 'No Account Found.')
                , 400);
        }

    }
}
