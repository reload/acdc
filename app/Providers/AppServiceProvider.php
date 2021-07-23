<?php

namespace App\Providers;

use App\ActiveCampaign;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Google\Client as GoogleClient;
use Google\Service\Sheets;

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
            return new ActiveCampaign(
                $app->make(Client::class),
                env('ACTIVECAMPAIGN_ACCOUNT', ''),
                env('ACTIVECAMPAIGN_TOKEN', '')
            );
        });

        $this->app->bind(GoogleClient::class, function ($app) {
            $client = new GoogleClient();
            $client->setApplicationName('ACDC');
            $client->setScopes([Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');

            //$client->setAuthConfig(json_decode($jsonAuth, true));
            print_r(env('GOOGLE_SERVICE_ACCOUNT'));
            $client->setAuthConfig(json_decode(env('GOOGLE_SERVICE_ACCOUNT'), true));
            return $client;
        });

        $this->app->bind(Sheets::class, function ($app) {
            return new Sheets($app->make(GoogleClient::class));
        });
    }
}
