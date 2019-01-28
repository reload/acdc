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
                $settings->get('activecampaign_account'),
                $settings->get('activecampaign_token')
            );
        });
    }
}
