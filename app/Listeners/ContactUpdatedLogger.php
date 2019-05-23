<?php

namespace App\Listeners;

use App\Events\ContactUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ContactUpdatedLogger
{
    /**
     * Handle the event.
     *
     * @param  ContactUpdated  $event
     * @return void
     */
    public function handle(ContactUpdated $event)
    {
        Log::info(sprintf('Contact %d updated', $event->contactId));
    }
}
