<?php

namespace Tests\Feature;

use App\Events\DealUpdated;
use App\Listeners\DealUpdatedLogger;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class DealUpdatedLoggerTest extends TestCase
{
    public function testLogging()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Deal 42 updated')
            ->andReturn();

        $logger = new DealUpdatedLogger();
        $logger->handle(new DealUpdated(42));
    }
}
