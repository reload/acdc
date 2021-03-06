<?php

namespace App\Listeners;

use App\Events\DealUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DealUpdatedLogger
{
    /**
     * Handle the event.
     *
     * @param  DealUpdated  $event
     * @return void
     */
    public function handle(DealUpdated $event)
    {
        Log::info(sprintf('Deal %d updated', $event->dealId));
    }
}
