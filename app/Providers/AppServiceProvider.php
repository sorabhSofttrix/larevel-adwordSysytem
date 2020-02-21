<?php

namespace App\Providers;

use Illuminate\Paginator;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind some classes related to the Google Ads API client library for
        // PHP.
        $this->app->bind(
            'Google\AdsApi\AdWords\AdWordsServices',
            function () {
                return new AdWordsServices();
            }
        );
        $this->app->bind(
            'Google\Auth\FetchAuthTokenInterface',
            function () {
                // Generate a refreshable OAuth2 credential for authentication
                // from the config file.
                return (new OAuth2TokenBuilder())->fromFile(
                    realpath(base_path('adsapi_php.ini'))
                )->build();
            }
        );
        $this->app->bind(
            'Google\AdsApi\AdWords\AdWordsSessionBuilder',
            function () {
                return new AdWordsSessionBuilder();
            }
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
