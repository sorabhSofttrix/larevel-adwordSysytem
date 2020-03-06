<?php

namespace App;

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
        'gtm', 'gtm_by', 'gtm_on',
        'acc_id' 
    ];
}
