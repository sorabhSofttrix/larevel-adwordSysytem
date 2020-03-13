<?php

namespace App\Http\Controllers;

use App\SetupStage;
use App\AllComment;
use App\AdwordsAccount;
use App\AccountChangeHistory;
use App\AccountStatusChange;
use Illuminate\Http\Request;
use Validator;

class SetupStageController extends Controller
{
    /**
     * Create a new SetupStageController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }

    /**
     * 
     * update stages 
     *  
     */
    public function updateSetupStages(Request $request){
        $valdRules = [
            'acc_id' => 'exists:setup_stages,acc_id',
            'stage_id' => 'exists:setup_stages,id',
        ];

        $validatedData = Validator::make($request->all(), $valdRules);

        if ($validatedData->fails()) {
            return response()->json(
                    getResponseObject(false, array(), 400, $validatedData->errors()->first())
                    , 400);
        } else {
            $stageQuery;
            $date = date('Y-m-d H:i:s');
            $user = auth()->user();
            if($user) {
                $stageQuery = SetupStage::select('setup_stages.*','g_acc_id','acc_name')
                                ->leftJoin('adwords_accounts','adwords_accounts.id','setup_stages.acc_id');
                if(isset($request['acc_id'])) {
                    $stageQuery->where('acc_id',$request->acc_id);
                } else if( isset($request['stage_id'])){
                    $stageQuery->where('id',$request->stage_id);
                } else {
                    return response()->json(
                        getResponseObject(true, '', 404, 'Account not found')
                        , 404);
                }
                $currentSatge = $stageQuery->firstOrFail();
                
                // if adding Keywords
                if(isset($request['keywords_url']) && $request->keywords_url != $currentSatge->keywords_url) {
                    $currentSatge->keywords = true;
                    $currentSatge->keywords_url = $request->keywords_url;
                    $currentSatge->keywords_by = $user->id;
                    $currentSatge->keywords_on = $date;
                }

                // if adding Adcopies
                if(isset($request['adcopies_url']) && $request->adcopies_url != $currentSatge->adcopies_url) {
                    $currentSatge->adcopies = true;
                    $currentSatge->adcopies_url = $request->adcopies_url;
                    $currentSatge->adcopies_by = $user->id;
                    $currentSatge->adcopies_on = $date;
                }

                // if adding keyword score
                if(isset($request['keywords_score'])) {
                    $currentSatge->keywords_score = $request->keywords_score;
                }

                // if adding adcopies score
                if(isset($request['adcopies_score'])) {
                    $currentSatge->adcopies_score = $request->adcopies_score;
                }

                // if adding peer reviews
                if(isset($request['peer_review']) && $request->peer_review) {
                    $currentSatge->peer_review = true;
                    $currentSatge->peer_review_by = $user->id;
                    $currentSatge->peer_review_on = $date;
                }

                // if adding client keywords & adcopies review
                if(isset($request['client_keyad_review']) && $request->client_keyad_review) {
                    $currentSatge->client_keyad_review = true;
                    $currentSatge->client_keyad_review_by = $user->id;
                    $currentSatge->client_keyad_review_on = $date;
                }

                // if adding campaign setup
                if(isset($request['campaign_setup']) && $request->campaign_setup) {
                    $currentSatge->campaign_setup = true;
                    $currentSatge->campaign_setup_by = $user->id;
                    $currentSatge->campaign_setup_on = $date;
                }

                // if adding client review
                if(isset($request['client_review']) && $request->client_review) {
                    $currentSatge->client_review = true;
                    $currentSatge->client_review_confirmed_by = $user->id;
                    $currentSatge->client_review_confirmed_on = $date;
                }

                // if adding conversion tracking
                if(isset($request['conversion_tracking']) && $request->conversion_tracking) {
                    $currentSatge->conversion_tracking = true;
                    $currentSatge->conversion_tracking_by = $user->id;
                    $currentSatge->conversion_tracking_on = $date;
                }


                // if adding google analytics
                if(isset($request['google_analytics']) && $request->google_analytics) {
                    $currentSatge->google_analytics = true;
                    $currentSatge->google_analytics_by = $user->id;
                    $currentSatge->google_analytics_on = $date;
                }

                // if adding gtm
                if(isset($request['gtm']) && $request->gtm) {
                    $currentSatge->gtm = true;
                    $currentSatge->gtm_by = $user->id;
                    $currentSatge->gtm_on = $date;
                }

                // if adding Comment
                if(isset($request['comment']) &&  isset($request->type)) {
                    $type = (in_array($request->type, array('peer_review','client_keyad_review'))) ? $request->type : '';
                    if($type) {
                        $comment = array(
                            'sub_type' => $type,
                            'entity_type' => 'stage',
                            'entity_id' => $currentSatge->id,
                            'add_by' => $user->id,
                            'comment' => $request->comment,
                        );
                        AllComment::create($comment);
                    }
                }
                $currentSatge->save();

                // Check if all stages are clear then move to management by making status active.
                if(
                    $currentSatge->keywords == true && 
                    $currentSatge->adcopies == true && 
                    $currentSatge->peer_review == true && 
                    $currentSatge->client_keyad_review == true && 
                    $currentSatge->campaign_setup == true && 
                    $currentSatge->client_review == true && 
                    $currentSatge->conversion_tracking == true && 
                    $currentSatge->google_analytics == true && 
                    $currentSatge->gtm == true
                ){
                    $account = AdwordsAccount::find($currentSatge->acc_id);
                    if ($account && $account->acc_status == 'setup') {
                        $changes = [];
                        $changes[] = changeHistoryField(
                            'acc_status', 'Account Status', 
                            $account->acc_status, 'active', 
                            'Account Status changed from `'.$account->acc_status.'` to `active`');
                        
                        // record for account history
                        $history = AccountChangeHistory::create([
                            'acc_id' => $account->id, 
                            'add_by' => $user->id, 
                            'changes' => $changes, 
                        ]);

                        // record for account status changes
                        $acc_status_change_record = AccountStatusChange::create(
                            array(
                                'add_by' => $user->id,
                                'new_value' => 'active',
                                'old_value' => $account->acc_status,
                                'reason_id' => null,
                                'comment' => null,
                                'up_comments' => null,
                                'rating' => null,
                                'acc_id' => $account->id,
                                'history_id' => $history->id,
                            )
                        );

                        // chnage account status
                        $account->acc_status = 'active';
                        $account->save();
                    }
                    
                }
                $acc = $this->getStageAccountResponse($currentSatge->id);
                return response()->json(
                    getResponseObject(true, $acc, 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 401, 'Unauthorized')
                    , 401);
            }
        }
    }

    public function getStageAccountResponse($id) {
        if($id) {
            $selectFields = array(
                'setup_stages.*',
                'adwords_accounts.g_acc_id','adwords_accounts.acc_name','adwords_accounts.acc_status', 
                'adwords_accounts.project_id',
                'directors.name as director_name', 'managers.name as manager_name',
                'projects.project_name',
                'keywords_user.name as keywords_user_name', 'adcopies_user.name as adcopies_user_name', 'client_keyad_user.name as client_keyad_user_name',
                'peer_review_user.name as peer_review_user_name', 'campaign_setup_user.name as campaign_setup_user_name', 'client_review_user.name as client_review_user_name',
                'conversion_tracking_user.name as conversion_tracking_user_name', 'google_analytics_user.name as google_analytics_user_name', 'gtm_user.name as gtm_user_name',
            );
            $stage = SetupStage::select($selectFields)
                        ->leftJoin('adwords_accounts','setup_stages.acc_id','=','adwords_accounts.id')
                        ->leftJoin('users as directors', 'adwords_accounts.account_director', '=', 'directors.id')
                        ->leftJoin('users as managers', 'adwords_accounts.account_manager', '=', 'managers.id')
                        ->leftJoin('projects', 'adwords_accounts.project_id', '=', 'projects.id')
                        ->leftJoin('users as keywords_user', 'setup_stages.keywords_by', '=', 'keywords_user.id')
                        ->leftJoin('users as adcopies_user', 'setup_stages.adcopies_by', '=', 'adcopies_user.id')
                        ->leftJoin('users as client_keyad_user', 'setup_stages.client_keyad_review_by', '=', 'client_keyad_user.id')
                        ->leftJoin('users as peer_review_user', 'setup_stages.peer_review_by', '=', 'peer_review_user.id')
                        ->leftJoin('users as campaign_setup_user', 'setup_stages.campaign_setup_by', '=', 'campaign_setup_user.id')
                        ->leftJoin('users as client_review_user', 'setup_stages.client_review_confirmed_by', '=', 'client_review_user.id')
                        ->leftJoin('users as conversion_tracking_user', 'setup_stages.conversion_tracking_by', '=', 'conversion_tracking_user.id')
                        ->leftJoin('users as google_analytics_user', 'setup_stages.google_analytics_by', '=', 'google_analytics_user.id')
                        ->leftJoin('users as gtm_user', 'setup_stages.gtm_by', '=', 'gtm_user.id')
                        ->where('setup_stages.id','=',$id)->get();
            $stageArr = $stage[0];
            $stageArr['peer_review_comments'] = $stage[0]->peer_review_comments();
            $stageArr['client_keyad_comments'] = $stage[0]->client_keyad_comments();
            return $stageArr;
        } else  {
            return null;
        }
    }
}
