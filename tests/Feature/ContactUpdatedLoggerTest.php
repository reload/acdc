<?php

namespace Tests\Feature;

use App\Events\ContactUpdated;
use App\Listeners\ContactUpdatedLogger;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class ContactUpdatedLoggerTest extends TestCase
{
    public function testLogging()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Contact 42 updated')
            ->andReturn();

        $logger = new ContactUpdatedLogger();
        $logger->handle(new ContactUpdated(42));
    }
}
