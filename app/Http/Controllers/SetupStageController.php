<?php

namespace App\Http\Controllers;

use App\SetupStage;
use App\AllComment;
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
                if(isset($request['acc_id'])) {
                    $stageQuery = SetupStage::where('acc_id',$request->acc_id);
                } else if( isset($request['stage_id'])){
                    $stageQuery = SetupStage::where('id',$request->stage_id);
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
                
                return response()->json(
                    getResponseObject(true, $currentSatge, 200, 'Unauthorized')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 401, 'Unauthorized')
                    , 401);
            }
        }
    }
}
