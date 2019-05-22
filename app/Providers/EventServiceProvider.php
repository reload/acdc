<?php

namespace App\Providers;

use App\Events\DealUpdated;
use App\Listeners\DealUpdatedLogger;
use App\Listeners\UpdateDealAverage;
use App\Listeners\UpdateDealSheets;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
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
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
