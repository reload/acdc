<?php

namespace Tests\Feature;

use App\Events\DealUpdated;
use App\Events\ContactUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    public function testDealEventDispatching()
    {
        Event::fake();

        $this->post('api/webhook', [
            'deal' => ['id' => 23],
        ]);

        Event::assertDispatched(DealUpdated::class);
    }

    public function testContactEventDispatching()
    {
        Event::fake();

        $this->post('api/webhook', [
            'contact' => ['id' => 23],
        ]);

        Event::assertDispatched(ContactUpdated::class);
    }

    public function testBadRequestNoEventDispatching()
    {
        Event::fake();

        $this->post('api/webhook', [
            'deal' => 'banana',
        ]);

        Event::assertNotDispatched(DealUpdated::class);
    }
}
