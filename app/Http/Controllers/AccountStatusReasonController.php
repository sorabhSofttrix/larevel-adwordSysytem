<?php

namespace App\Http\Controllers;

use App\AccountStatusReason;
use Illuminate\Http\Request;
use Validator;

class AccountStatusReasonController extends Controller
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

    public function addNewReasonInList(Request $request){
        $valdRules = [
            'title' => 'required|string',
            'rank' => 'integer',
            'sortOrder' => 'integer',
        ];
        if(isset($request['id'])) {
            $valdRules['id'] = 'required|exists:account_status_reasons,id'; 
        }

        $validatedData = Validator::make($request->all(), $valdRules);
        if ($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            if(in_array($user->Roles()->pluck('id')->first(), array(1,2))) {
                $addReason = null;
                if($request->id) {
                    $addReason = AccountStatusReason::find($request->id);
                    (isset($request['title'])) ?  $addReason->title = $request->title : '';
                    (isset($request['rank'])) ?   $addReason->rank = $request->rank : '';
                    (isset($request['sortOrder'])) ?   $addReason->sortOrder = $request->sortOrder : '';
                    $addReason->save();
                } else {
                    $reason =  array(
                        'title' => $request->title,
                        'add_by' => $user->id,
                    );
                    (isset($request['rank'])) ?  $reason['rank'] = $request->rank : '';
                    (isset($request['sortOrder'])) ?  $reason['sortOrder'] = $request->rank : '';
                    $addReason = AccountStatusReason::create($reason);
                }

                if($addReason) {
                    return response()->json(
                        getResponseObject(true, $addReason , 200, '')
                        , 200);
                } else {
                    return response()->json(
                        getResponseObject(false, '' , 400, 'Opps! Something went wrong please try again.')
                        , 401);
                }

            } else {
                return response()->json(
                    getResponseObject(false, array() , 401, 'Unauthorized User')
                    , 401);
            }   
        }
    }

    public function getAllResasonsList(){
        $reasons = AccountStatusReason::orderBy('sortOrder')->where('is_active',true)->get();
        return response()->json(
            getResponseObject(true, $reasons , 200, '')
            , 200);
    }

    public function deleteResasonsFromList(Request $request){
        $valdRules = array(
            'id' => 'required|exists:account_status_reasons,id'
        ); 
        $validatedData = Validator::make($request->all(), $valdRules);
        if ($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            if(in_array($user->Roles()->pluck('id')->first(), array(1,2))) {
                $reason = AccountStatusReason::find($request->id);
                $reason->is_active = false;
                $reason->deleted_by = $user->id;
                $reason->deleted_at = date('Y-m-d H:i:s');
                $reason->save();
                return response()->json(
                    getResponseObject(true, 'Item Deleted' , 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, array() , 401, 'Unauthorized User')
                    , 401);
            }
        }
    }

}
