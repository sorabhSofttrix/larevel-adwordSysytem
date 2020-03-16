<?php

namespace App;
use App\AllComment;

use Illuminate\Database\Eloquent\Model;

class SetupStage extends Model
{
    //
    protected $fillable = [ 
        'keywords', 'keywords_url', 'keywords_by', 'keywords_on', 'keywords_score', 
        'adcopies', 'adcopies_url', 'adcopies_by', 'adcopies_on', 'adcopies_score', 
        'peer_review', 'peer_review_by', 'peer_review_on', 
        'client_keyad_review', 'client_keyad_review_by', 'client_keyad_review_on', 
        'campaign_setup', 'campaign_setup_by', 'campaign_setup_on', 
        'client_review', 'client_review_confirmed_by', 'client_review_confirmed_on',
        'conversion_tracking', 'conversion_tracking_by', 'conversion_tracking_on', 
        'google_analytics', 'google_analytics_by', 'google_analytics_on',
        'gtm', 'gtm_by', 'gtm_on','campaign_live','campaign_live_by','campaign_live_on',
        'acc_id' 
    ];

    public function peer_review_comments() {
        $peer_comments = AllComment::select(
                            'comment','all_comments.id','all_comments.add_by',
                            'users.name as add_by_name', 
                            'all_comments.created_at', 'all_comments.updated_at'
                            )
                            ->where('entity_type','stage')
                            ->where('entity_id',$this->id)
                            ->where('sub_type','peer_review')
                            ->leftJoin('users','all_comments.add_by','users.id')
                            ->get();
        return $peer_comments;
    }

    public function client_keyad_comments() {
        $client_keyad_comments = AllComment::select(
                            'comment','all_comments.id','all_comments.add_by',
                            'users.name as add_by_name', 
                            'all_comments.created_at', 'all_comments.updated_at'
                            )
                            ->where('entity_type','stage')
                            ->where('entity_id',$this->id)
                            ->where('sub_type','client_keyad_review')
                            ->leftJoin('users','all_comments.add_by','users.id')
                            ->get();
        return $client_keyad_comments;
    }
}
