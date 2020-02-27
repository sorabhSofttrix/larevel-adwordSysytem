<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');

Route::group([
    'prefix' => 'auth'

], function () {

    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::post('update-user', 'AuthController@update_user_profile');
    Route::get('get-user', 'AuthController@getUser');
    Route::get('get-user-team', 'AuthController@usersTeam');
    Route::get('get-team', 'AuthController@usersTeamByRoles');
    
    // Route::post('add-account', 'AdwordsAccountController@addAdwordsAccount');
    Route::post('update-account', 'AdwordsAccountController@updateAdwordsAccount');
    Route::get('get-accounts', 'AdwordsAccountController@getAdwordsAccount');
    Route::get('get-account-info', 'AdwordsAccountController@getAccountInfo');
    Route::get('get-unassingned-accounts', 'AdwordsAccountController@getUnassignedAccounts');
    Route::post('update-unassingned-accounts', 'AdwordsAccountController@updateUnassignedAccounts');
    

    Route::get('sync-gaccounts', 'AccountSyncController@syncFromGoogle');
    Route::get('cron-compare', 'AccountSyncController@cronCompare');
    Route::get('send-pending-email', 'AccountSyncController@sendPendingMails');

    
    Route::get('get-dashboard-alerts', 'AlertController@getAllAlertsForDashboard');
    Route::get('get-alerts-count', 'AlertController@getAlertsCountForDashboard');
    Route::post('update-alert', 'AlertController@updateAlert');  


    Route::get('account-status-summary', 'AccountChangeHistoryController@getAccountMonthlyStatusSummary');
    Route::post('account-dated-status', 'AccountChangeHistoryController@getAccountsInDateRange');
    Route::get('account-dated-status', 'AccountChangeHistoryController@getAccountsInDateRange');
});
