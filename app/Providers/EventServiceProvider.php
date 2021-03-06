<?php

namespace App\Providers;

use App\Events\DealUpdated;
use App\Events\ContactUpdated;
use App\Listeners\DealUpdatedLogger;
use App\Listeners\ContactUpdatedLogger;
use App\Listeners\UpdateDealAverage;
use App\Listeners\UpdateDealSheets;
use App\Listeners\UpdateContactSheets;
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
        ContactUpdated::class => [
            ContactUpdatedLogger::class,
            UpdateContactSheets::class
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
