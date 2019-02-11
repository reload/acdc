<?php

namespace App\Providers;

use App\ActiveCampaign;
use App\Settings;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ActiveCampaign::class, function ($app) {
            $settings = $app->make(Settings::class);
            return new ActiveCampaign(
                $app->make(Client::class),
                env('ACTIVECAMPAIGN_ACCOUNT', ''),
                env('ACTIVECAMPAIGN_TOKEN', '')
            );
        });

        $this->app->bind(\Google_Client::class, function ($app) {
            $client = new \Google_Client();
            $client->setApplicationName('ACDC');
            $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');

            //$client->setAuthConfig(json_decode($jsonAuth, true));
            $client->setAuthConfig(json_decode(env('GOOGLE_SERVICE_ACCOUNT'), true));
            return $client;
        });

        $this->app->bind(\Google_Service_Sheets::class, function ($app) {
            return new \Google_Service_Sheets($app->make(\Google_Client::class));
        });
    }
}
