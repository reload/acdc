<?php

namespace App\Providers;

use App\ActiveCampaign;
use App\Events\ContactUpdated;
use App\Events\DealUpdated;
use App\Listeners\ContactUpdatedLogger;
use App\Listeners\DealUpdatedLogger;
use App\Listeners\UpdateContactSheets;
use App\Listeners\UpdateDealAverage;
use App\Listeners\UpdateDealSheets;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Google\Client as GoogleClient;
use Google\Service\Sheets;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        DealUpdated::class => [
            DealUpdatedLogger::class,
            UpdateDealAverage::class,
            UpdateDealSheets::class
        ],
        ContactUpdated::class => [
            ContactUpdatedLogger::class,
            UpdateContactSheets::class
        ],
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
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

            $client->setAuthConfig(json_decode(env('GOOGLE_SERVICE_ACCOUNT'), true));
            return $client;
        });

        $this->app->bind(Sheets::class, function ($app) {
            return new Sheets($app->make(GoogleClient::class));
        });
    }
}
